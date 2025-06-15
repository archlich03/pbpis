<?php

namespace App\Http\Controllers;

use App\Models\Body;
use App\Models\User;
use App\Models\Meeting;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BodyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bodies = Body::orderBy('title', 'asc')->get();
        $users = User::orderBy('name', 'asc')->get();

        return view('bodies.panel', ['bodies' => $bodies, 'users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }
        $users = User::orderBy('name', 'asc')->get();
        return view('bodies.create', ['users' => $users]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'is_ba_sp' => ['required', 'in:0,1'],
            'classification' => ['required', 'string', 'max:16'],
            'chairman_id' => ['required', 'integer', 'exists:users,user_id'],
            'members' => ['array'],
            'members.*' => ['integer', 'exists:users,user_id'],
        ]);

        $body = new Body();
        $body->title = $request->input('title');
        $body->classification = $request->input('classification');
        $body->is_ba_sp = $request->input('is_ba_sp');
        $body->chairman_id = $request->input('chairman_id');
        
        $members = $request->input('members', []);
        sort($members); // Sort the members array

        $body->members = $members;
        $body->save();

        return redirect()->route('bodies.panel');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $body = Body::findOrFail($id);
        //$membersIds = $body->members ?? [];
        //$members = User::whereIn('user_id', $membersIds)->orderBy('name')->get();

        return view('bodies.show', ['body' => $body]);//, 'members' => $members]);
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
        $body = Body::findOrFail($id);
        $users = User::orderBy('name', 'asc')->get();
        return view('bodies.edit', ['body' => $body, 'users' => $users]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'is_ba_sp' => ['required', 'in:0,1'],
            'classification' => ['required', 'string', 'max:16'],
            'chairman_id' => ['required', 'integer', 'exists:users,user_id'],
            'members' => ['array'],
            'members.*' => ['integer', 'exists:users,user_id'],
        ]);

        $body = Body::findOrFail($id);
        $body->title = $request->input('title');
        $body->is_ba_sp = $request->input('is_ba_sp');
        $body->classification = $request->input('classification');
        $body->chairman_id = $request->input('chairman_id');
        
        $members = $request->input('members', []);
        sort($members); // Sort the members array

        $body->members = $members;
        $body->save();

        return redirect()->route('bodies.panel');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $body = Body::findOrFail($id);

        // Delete all meetings, that are assigned to this body
        $meetings = Meeting::where('body_id', $id)->get();
        foreach ($body->meetings as $meeting) {
            foreach ($meeting->questions as $question) {
                foreach($question->votes() as $vote) {
                    $vote->delete();
                }
                $question->delete();
            }
            $meeting->delete();
        }

        $body->delete();

        return redirect()->route('bodies.panel');
    }
}

