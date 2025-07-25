<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Meeting $meeting, Question $question)
    {

        if (!$meeting->body->members->contains(Auth::user())) {
            abort(403);
        }
        

        
        // Auto-attendance marking will happen after vote is saved
        
        $request->validate([
            'choice' => ['required', 'string', 'in:' . implode(',', \App\Models\Vote::STATUSES)],
        ]);

        $vote = $question->voteByUser(Auth::user());

        if (!$vote) {
            $vote = new Vote();
            $vote->question_id = $question->question_id;
            $vote->user_id = Auth::user()->user_id;
        }
        $vote->choice = $request->input('choice');
        if (now() <= $meeting->vote_end && now() >= $meeting->vote_start) {            
            $vote->save();
            
            // Auto-mark user as attending when they vote
            if (!$meeting->isUserAttending(Auth::user())) {
                $meeting->attendances()->create([
                    'user_id' => Auth::user()->user_id,
                ]);
            }
        }

        return redirect()->route('meetings.show', ['meeting' => $meeting]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Meeting $meeting, Question $question)
    {
        $vote = $question->voteByUser(Auth::user());
        if (!$meeting->body->members->contains(Auth::user())) {
            abort(403);
        }

        // Check if vote exists before trying to delete
        if (!$vote) {
            return redirect()->back()->with('error', __('No vote found to delete.'));
        }

        $vote->delete();
        
        // Check if user has any votes left in this meeting
        $userHasVotes = $meeting->questions()->whereHas('votes', function($query) {
            $query->where('user_id', Auth::user()->user_id);
        })->exists();
        
        // If user has no votes left, mark them as absent
        if (!$userHasVotes && $meeting->isUserAttending(Auth::user())) {
            $meeting->attendances()->where('user_id', Auth::user()->user_id)->delete();
        }

        return redirect()->route('meetings.show', ['meeting' => $meeting]);
    }

    /**
     * Store a proxy vote on behalf of another user
     */
    public function storeProxy(Meeting $meeting, Question $question, Request $request)
    {
        // Only secretaries and IT admins can cast proxy votes
        if (!Auth::user()->isPrivileged() && !Auth::user()->isSecretary()) {
            abort(403, 'Only secretaries and IT administrators can cast proxy votes.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'choice' => 'required|in:' . implode(',', \App\Models\Vote::STATUSES),
        ]);

        $targetUser = \App\Models\User::findOrFail($request->user_id);
        
        // Check if target user is a member of the body
        if (!$meeting->body->members->contains($targetUser)) {
            abort(403, 'User is not a member of this body.');
        }

        // Delete existing vote if any
        $question->votes()->where('user_id', $targetUser->user_id)->delete();

        // Create new vote
        $question->votes()->create([
            'user_id' => $targetUser->user_id,
            'choice' => $request->choice,
        ]);

        // Auto-mark target user as attending when vote is cast on their behalf
        if (!$meeting->isUserAttending($targetUser)) {
            $meeting->attendances()->create([
                'user_id' => $targetUser->user_id,
            ]);
        }

        return redirect()->route('meetings.show', ['meeting' => $meeting])
            ->with('success', 'Proxy vote cast successfully for ' . $targetUser->name);
    }

    /**
     * Remove a proxy vote on behalf of another user
     */
    public function destroyProxy(Meeting $meeting, Question $question, Request $request)
    {
        // Only secretaries and IT admins can remove proxy votes
        if (!Auth::user()->isPrivileged() && !Auth::user()->isSecretary()) {
            abort(403, 'Only secretaries and IT administrators can remove proxy votes.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,user_id',
        ]);

        $targetUser = \App\Models\User::findOrFail($request->user_id);
        
        // Check if target user is a member of the body
        if (!$meeting->body->members->contains($targetUser)) {
            abort(403, 'User is not a member of this body.');
        }

        // Delete the vote
        $question->votes()->where('user_id', $targetUser->user_id)->delete();
        
        // Check if target user has any votes left in this meeting
        $userHasVotes = $meeting->questions()->whereHas('votes', function($query) use ($targetUser) {
            $query->where('user_id', $targetUser->user_id);
        })->exists();
        
        // If target user has no votes left, mark them as absent
        if (!$userHasVotes && $meeting->isUserAttending($targetUser)) {
            $meeting->attendances()->where('user_id', $targetUser->user_id)->delete();
        }

        return redirect()->route('meetings.show', ['meeting' => $meeting])
            ->with('success', 'Proxy vote removed successfully for ' . $targetUser->name);
    }
}

