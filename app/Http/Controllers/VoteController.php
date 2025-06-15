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
        $request->validate([
            'choice' => ['required', 'string'],
        ]);

        $vote = $question->voteByUser(Auth::user());

        if (!$vote) {
            $vote = new Vote();
            $vote->question_id = $question->question_id;
            $vote->user_id = Auth::user()->user_id;
        }
        $vote->choice = $request->input('choice');
        $vote->save();

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

        $vote->delete();

        return redirect()->route('meetings.show', ['meeting' => $meeting]);
    }
}

