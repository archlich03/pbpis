<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Toggle attendance for a user in a meeting
     *
     * @param Request $request
     * @param Meeting $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggle(Request $request, Meeting $meeting)
    {
        // Check if attendance can be modified (only during meeting)
        $now = Carbon::now();
        if ($now < $meeting->vote_start || $now > $meeting->vote_end) {
            return redirect()->back()->with('error', __('Attendance can only be modified during the meeting.'));
        }

        $userId = $request->input('user_id');
        
        // Authorization check
        if (!$this->canManageAttendance($meeting, $userId)) {
            abort(403);
        }

        // Check if user is a body member
        $user = User::findOrFail($userId);
        if (!$meeting->body->members->contains($user)) {
            return redirect()->back()->with('error', __('User is not a member of this body.'));
        }

        // Toggle attendance
        $attendance = MeetingAttendance::where('meeting_id', $meeting->meeting_id)
                                     ->where('user_id', $userId)
                                     ->first();

        if ($attendance) {
            // Remove attendance
            $attendance->delete();
            $message = __('Attendance removed successfully.');
        } else {
            // Add attendance
            MeetingAttendance::create([
                'meeting_id' => $meeting->meeting_id,
                'user_id' => $userId,
            ]);
            $message = __('Attendance marked successfully.');
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Mark all body members as present
     *
     * @param Meeting $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllPresent(Meeting $meeting)
    {
        // Check if attendance can be modified (only during meeting)
        $now = Carbon::now();
        if ($now < $meeting->vote_start || $now > $meeting->vote_end) {
            return redirect()->back()->with('error', __('Attendance can only be modified during the meeting.'));
        }

        // Authorization check - only privileged users can mark all present
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $attendanceRecords = [];
        foreach ($meeting->body->members as $member) {
            // Check if attendance record already exists
            $existingAttendance = MeetingAttendance::where('meeting_id', $meeting->meeting_id)
                                                  ->where('user_id', $member->user_id)
                                                  ->exists();
            
            if (!$existingAttendance) {
                $attendanceRecords[] = [
                    'meeting_id' => $meeting->meeting_id,
                    'user_id' => $member->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($attendanceRecords)) {
            MeetingAttendance::insert($attendanceRecords);
            $count = count($attendanceRecords);
            return redirect()->back()->with('success', __(':count members marked as present.', ['count' => $count]));
        }

        return redirect()->back()->with('info', __('All members are already marked as present.'));
    }

    /**
     * Auto-mark attendance based on voting participation
     *
     * @param Meeting $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function autoMarkFromVotes(Meeting $meeting)
    {
        // Authorization check - only privileged users
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $voterIds = [];
        foreach ($meeting->questions as $question) {
            foreach ($question->votes as $vote) {
                $voterIds[] = $vote->user_id;
            }
        }
        
        $voterIds = array_unique($voterIds);
        $attendanceRecords = [];
        
        foreach ($voterIds as $voterId) {
            // Check if attendance record already exists
            $existingAttendance = MeetingAttendance::where('meeting_id', $meeting->meeting_id)
                                                  ->where('user_id', $voterId)
                                                  ->exists();
            
            if (!$existingAttendance) {
                $attendanceRecords[] = [
                    'meeting_id' => $meeting->meeting_id,
                    'user_id' => $voterId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($attendanceRecords)) {
            MeetingAttendance::insert($attendanceRecords);
            $count = count($attendanceRecords);
            return redirect()->back()->with('success', __(':count members auto-marked as present based on voting.', ['count' => $count]));
        }

        return redirect()->back()->with('info', __('No new attendance records created from voting data.'));
    }

    /**
     * Mark non-voting members as absent
     *
     * @param Meeting $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markNonVotersAbsent(Meeting $meeting)
    {
        // Only privileged users can perform this action
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        // Check if attendance can be modified (only during meeting)
        $now = Carbon::now();
        if ($now < $meeting->vote_start || $now > $meeting->vote_end) {
            return redirect()->back()->with('error', __('Attendance can only be modified during the meeting.'));
        }

        $count = 0;
        
        // Get all currently present members
        $presentMembers = $meeting->attendees;
        
        foreach ($presentMembers as $member) {
            // Check if member has voted on any question in this meeting
            $hasVoted = $meeting->questions()->whereHas('votes', function($query) use ($member) {
                $query->where('user_id', $member->user_id);
            })->exists();
            
            // If member hasn't voted, mark them as absent
            if (!$hasVoted) {
                $meeting->attendances()->where('user_id', $member->user_id)->delete();
                $count++;
            }
        }

        if ($count > 0) {
            return redirect()->back()->with('success', __(':count non-voting members marked as absent.', ['count' => $count]));
        }

        return redirect()->back()->with('info', __('No members were marked as absent (all present members have voted).'));
    }

    /**
     * Check if the current user can manage attendance for a specific user
     *
     * @param Meeting $meeting
     * @param int $userId
     * @return bool
     */
    private function canManageAttendance(Meeting $meeting, int $userId): bool
    {
        $currentUser = Auth::user();
        
        // IT administrators and secretaries can manage anyone's attendance
        if ($currentUser->isPrivileged()) {
            return true;
        }
        
        // Users can only manage their own attendance
        if ($currentUser->user_id == $userId && $meeting->body->members->contains($currentUser)) {
            return true;
        }
        
        return false;
    }
}
