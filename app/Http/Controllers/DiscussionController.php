<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\MeetingAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DiscussionController extends Controller
{
    /**
     * Store a new discussion comment.
     */
    public function store(Request $request, Meeting $meeting, Question $question): RedirectResponse
    {
        $user = Auth::user();

        // Authorization: Only during voting phase (Vyksta)
        if ($meeting->status !== 'Vyksta') {
            abort(403, 'Discussions are only allowed during voting phase.');
        }

        // Only e-vote meetings have discussions
        if (!$meeting->is_evote) {
            abort(403, 'Discussions are only available for e-vote meetings.');
        }

        // Check if user is authorized (body member, secretary, or IT admin)
        $isBodyMember = $meeting->body->members->contains($user);
        $isSecretary = $user->role === 'Sekretorius';
        $isITAdmin = $user->role === 'IT administratorius';

        if (!$isBodyMember && !$isSecretary && !$isITAdmin) {
            abort(403, 'You are not authorized to participate in this discussion.');
        }

        // Rate limiting: 1 message per user per minute
        $lastComment = Discussion::where('user_id', $user->user_id)
            ->where('question_id', $question->question_id)
            ->where('created_at', '>', now()->subMinute())
            ->first();
        
        if ($lastComment) {
            $secondsRemaining = 60 - now()->diffInSeconds($lastComment->created_at);
            return redirect()
                ->route('meetings.show', $meeting)
                ->with('error', __('Please wait :seconds seconds before posting another comment.', ['seconds' => $secondsRemaining]));
        }

        // Validate
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:discussions,discussion_id',
        ]);

        // If parent_id is provided, verify it belongs to the same question
        if (!empty($validated['parent_id'])) {
            $parent = Discussion::findOrFail($validated['parent_id']);
            if ($parent->question_id !== $question->question_id) {
                abort(403, 'Cannot reply to a discussion from a different question.');
            }
        }

        // Create discussion
        $discussion = Discussion::create([
            'question_id' => $question->question_id,
            'user_id' => $user->user_id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
        ]);

        // Auto-mark body members as attending when they post a comment
        if ($isBodyMember) {
            $attendance = MeetingAttendance::firstOrCreate(
                [
                    'meeting_id' => $meeting->meeting_id,
                    'user_id' => $user->user_id,
                ],
                [
                    'status' => 'Dalyvauja',
                ]
            );
            
            // If attendance record exists but status is not attending, update it
            if ($attendance->status !== 'Dalyvauja') {
                $attendance->update(['status' => 'Dalyvauja']);
            }
        }

        // Store active question in session to preserve tab state
        session(['active_question_id' => $question->question_id]);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('status', 'discussion-created');
    }

    /**
     * Update an existing discussion comment.
     */
    public function update(Request $request, Meeting $meeting, Question $question, Discussion $discussion): RedirectResponse
    {
        $user = Auth::user();

        // Authorization: Only during voting phase
        if ($meeting->status !== 'Vyksta') {
            abort(403, 'Discussions can only be edited during voting phase.');
        }

        // Only e-vote meetings have discussions
        if (!$meeting->is_evote) {
            abort(403);
        }

        // Only the author can edit their own discussion
        if ($discussion->user_id !== $user->user_id) {
            abort(403, 'You can only edit your own comments.');
        }

        // Validate
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        // Update
        $discussion->update([
            'content' => $validated['content'],
        ]);

        // Store active question in session to preserve tab state
        session(['active_question_id' => $question->question_id]);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('status', 'discussion-updated');
    }

    /**
     * Delete a discussion comment.
     */
    public function destroy(Request $request, Meeting $meeting, Question $question, Discussion $discussion): RedirectResponse
    {
        $user = Auth::user();

        // Authorization: Only during voting phase
        if ($meeting->status !== 'Vyksta') {
            abort(403, 'Discussions can only be deleted during voting phase.');
        }

        // Only e-vote meetings have discussions
        if (!$meeting->is_evote) {
            abort(403);
        }

        // Check authorization: author, secretary, or IT admin
        $isAuthor = $discussion->user_id === $user->user_id;
        $isSecretary = $user->role === 'Sekretorius';
        $isITAdmin = $user->role === 'IT administratorius';

        if (!$isAuthor && !$isSecretary && !$isITAdmin) {
            abort(403, 'You are not authorized to delete this comment.');
        }

        // Soft delete (will cascade to replies)
        $discussion->delete();

        // Store active question in session to preserve tab state
        session(['active_question_id' => $question->question_id]);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('status', 'discussion-deleted');
    }
}
