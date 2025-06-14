<?php

namespace App\Http\Controllers;

use App\Models\Body;
use App\Models\User;
use Illuminate\Http\Request;

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
        $membersIds = $body->members ?? [];
        $members = User::whereIn('user_id', $membersIds)->orderBy('name')->get();

        return view('bodies.show', ['body' => $body, 'members' => $members]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

