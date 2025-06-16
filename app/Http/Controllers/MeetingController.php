<?php

namespace App\Http\Controllers;

use App\Models\Body;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $meetings = Meeting::orderBy('meeting_date', 'desc')->get();

        return view('meetings.panel', ['meetings' => $meetings]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Body $body)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $users = User::orderBy('name', 'asc')->get();
        return view('meetings.create', ['body' => $body, 'users' => $users]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Body $body)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $request->validate([
            'secretary_id' => ['required', 'integer', 'exists:users,user_id'],
            'is_evote' => ['required', 'in:0,1'],
            'meeting_date' => ['required', 'date'],
            'vote_start' => ['nullable', 'date'],
            'vote_end' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('vote_start') && $request->input('vote_end') && $request->input('vote_start') > $request->input('vote_end')) {
                    $fail('Vote end must be later than vote start');
                }
            }],
        ]);

        $meeting = new Meeting();
        $meeting->secretary_id = $request->input('secretary_id');
        $meeting->body_id = $body->body_id;
        $meeting->is_evote = $request->input('is_evote');
        $meeting->meeting_date = $request->input('meeting_date');
        $meeting->vote_start = $request->input('vote_start');
        $meeting->vote_end = $request->input('vote_end');
        $meeting->save();

        return redirect()->route('bodies.show', $body);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::findOrFail($id);
        $users = User::orderBy('name', 'asc')->get();
        
        if ($meeting->status != 'Vyksta' && now() >= $meeting->vote_start && now() <= $meeting->vote_end) {
            $meeting->status = 'Vyksta';
            $meeting->save();
        } elseif ($meeting->status != 'Baigtas' && now() >= $meeting->vote_end) {
            $meeting->status = 'Baigtas';
            $meeting->save();
        }

        return view('meetings.show', ['meeting' => $meeting, 'users' => $users]);//, 'members' => $members]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $meeting = Meeting::findOrFail($id);
        $bodies = Body::orderBy('title', 'asc')->get();
        $users = User::orderBy('name', 'asc')->get();
        return view('meetings.edit', ['meeting' => $meeting, 'bodies' => $bodies, 'users' => $users]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Meeting $meeting)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
    
        $request->validate([
            'secretary_id' => ['required', 'integer', 'exists:users,user_id'],
            'is_evote' => ['required', 'in:0,1'],
            'meeting_date' => ['required', 'date'],
            'vote_start' => ['nullable', 'date'],
            'vote_end' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('vote_start') && $request->input('vote_end') && $request->input('vote_start') > $request->input('vote_end')) {
                    $fail('Vote end must be later than vote start');
                }
            }],
        ]);

        $meeting->status = 'Suplanuotas';
        if (now() >= $meeting->vote_start && now() <= $meeting->vote_end) {
            $meeting->status = 'Vyksta';
        } elseif (now() >= $meeting->vote_end) {
            $meeting->status = 'Baigtas';
        }
        $meeting->secretary_id = $request->input('secretary_id');
        $meeting->is_evote = $request->input('is_evote');
        $meeting->meeting_date = $request->input('meeting_date');
        $meeting->vote_start = $request->input('vote_start');
        $meeting->vote_end = $request->input('vote_end');
        $meeting->save();
        $users = User::orderBy('name', 'asc')->get();

        return redirect()->route('meetings.show', ['meeting' => $meeting]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $meeting = Meeting::findOrFail($id);
        $body = $meeting->body; // Get the associated body before deleting the meeting

        // Step 1: Get all questions related to this meeting
        $questions = $meeting->questions; // This fetches the collection of Question models

        // Step 2: Iterate through each question and delete its associated votes
        foreach ($questions as $question) {
            $question->votes()->delete(); // Correctly deletes votes for each question
        }

        // Step 3: After all votes are deleted, delete the questions belonging to this meeting
        $meeting->questions()->delete(); // Correctly deletes questions associated with this meeting

        // Step 4: Finally, delete the meeting itself
        $meeting->delete();

        // Redirect back to the show page of the parent body
        return redirect()->route('bodies.show', $body)
                         ->with('success', 'Meeting and all related data deleted successfully.');
    }

    
    public function protocol(Meeting $meeting)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        return view('meetings.protocol', ['meeting' => $meeting]);
    }

    public function protocolPDF(Meeting $meeting)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $pdf = PDF::loadView('meetings.protocol', ['meeting' => $meeting]);
        $name = $meeting->body->title . ' ' . ($meeting->body->is_ba_sp ? 'BA' : 'MA') . ' ' . $meeting->meeting_date->format('Y-m-d') . ' protokolas.pdf';
        return $pdf->download($name); 
    }
}