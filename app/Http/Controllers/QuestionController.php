<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Meeting $meeting)
    {
        /*if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $questions = $meeting->questions()->orderBy('question_id', 'asc')->get();

        return view('questions.panel', ['questions' => $questions]);*/
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Meeting $meeting)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $users = User::orderBy('name')->get();

        return view('questions.create', ['meeting' => $meeting, 'users' => $users]);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Meeting $meeting)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $request->validate([
            'title' => ['required', 'string'],
            'decision' => ['nullable', 'string'],
            'presenter_id' => ['required', 'integer', 'exists:users,user_id'],
            'type' => ['required', 'string'],
            'summary' => ['nullable', 'string'],
        ]);

        $question = new Question();
        $question->meeting_id = $meeting->meeting_id;
        $question->title = $request->input('title');
        $question->decision = $request->input('decision');
        $question->presenter_id = $request->input('presenter_id');
        $question->type = $request->input('type');
        $question->summary = $request->input('summary');
        $question->save();

        return redirect()->route('meetings.show', ['meeting' => $meeting]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /*public function show($id)
    {
        $question = Question::findOrFail($id);
        $users = User::orderBy('name', 'asc')->get();

        return view('questions.show', ['question' => $question, 'users' => $users]);
    }*/

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Question $question)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        return view('questions.edit', ['question' => $question]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Meeting $meeting, Question $question)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $request->validate([
            'title' => ['required', 'string'],
            'decision' => ['nullable', 'string'],
            'presenter_id' => ['required', 'integer', 'exists:users,user_id'],
            'type' => ['required', 'string'],
            'summary' => ['nullable', 'string'],
        ]);

        $question->title = $request->input('title');
        $question->decision = $request->input('decision');
        $question->presenter_id = $request->input('presenter_id');
        $question->type = $request->input('type');
        $question->summary = $request->input('summary');
        $question->save();
        $users = User::orderBy('name', 'asc')->get();

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
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        if ($meeting != $question->meeting) {
            abort(402);
        }
        $meeting = $question->meeting;
        $question->delete();

        return redirect()->route('meetings.show', ['meeting' => $meeting]);
    }
}

