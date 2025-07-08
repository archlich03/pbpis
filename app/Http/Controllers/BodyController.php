<?php

namespace App\Http\Controllers;

use App\Models\Body;
use App\Models\User;
use App\Models\Meeting;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;


class BodyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = in_array((int) $request->input('perPage'), [10, 20, 50, 100]) ? (int) $request->input('perPage') : 20;

        $bodies = Body::when($search, function ($query, $search) {
                return $query->where('title', 'like', '%' . $search . '%');
            })
            ->orderBy($request->input('sort', 'title'), $request->input('direction', 'asc'))
            ->paginate($perPage)
            ->withQueryString();

        $users = User::orderBy('name', 'asc')->get();

        return view('bodies.index', compact('bodies', 'users', 'perPage'));
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
        if (!Auth::user()->isAdmin()) {
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

        return redirect()->route('bodies.index');
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

        $meetings = $body->meetings()->orderBy('meeting_date', 'desc')->limit(5)->get();
        foreach ($meetings as $meeting) {
            if ($meeting->status != 'Suplanuotas' && now() < $meeting->vote_start && now()) {
                $meeting->status = 'Suplanuotas';
                $meeting->save();
            } elseif ($meeting->status != 'Vyksta' && now() >= $meeting->vote_start && now() <= $meeting->vote_end) {
                $meeting->status = 'Vyksta';
                $meeting->save();
            } elseif ($meeting->status != 'Baigtas' && now() >= $meeting->vote_end) {
                $meeting->status = 'Baigtas';
                $meeting->save();
            }
        }

        return view('bodies.show', ['body' => $body]);
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

        return redirect()->route('bodies.index');
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

        // Get all meetings belonging to this body
        $meetings = $body->meetings; // This fetches the collection of Meeting models

        foreach ($meetings as $meeting) {
            // For each meeting, get its questions
            $questions = $meeting->questions; // This fetches the collection of Question models

            foreach ($questions as $question) {
                // For each question, delete its votes
                $question->votes()->delete(); // Correctly delete votes
            }

            // After deleting all votes for questions in this meeting, delete the questions
            $meeting->questions()->delete(); // Correctly delete questions
        }

        // After deleting all questions (and their votes) for all meetings, delete the meetings
        $body->meetings()->delete(); // Correctly delete meetings

        // Finally, delete the body itself
        $body->delete();

        return redirect()->route('bodies.index');
    }
}

