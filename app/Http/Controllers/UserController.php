<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Body;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(): View
    {
        $perPage = request('perPage', 20);
        $sort = request('sort', 'name');
        $direction = request('direction', 'asc');
        $search = request('search');

        $query = User::query();

        // Apply search if present
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Validate and apply sorting
        if (!in_array($sort, ['name', 'email'])) $sort = 'name';
        if (!in_array($direction, ['asc', 'desc'])) $direction = 'asc';

        $users = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return view('users.panel', compact('users'));
    }

    
    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user]);
    }

    
    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('users.panel');
    }

    
    /**
     * Update the specified user's profile in storage.
     */
    public function updateProfile(Request $request, User $user): RedirectResponse
    {
        $role = Auth::user()->role;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->user_id . ',user_id'],
            'pedagogical_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['integer', 'in:0,1'],
            'role' => $role === 'Sekretorius'
                ? ['string', 'in:Balsuojantysis']
                : ($role === 'IT administratorius'
                    ? ['string', 'in:Balsuojantysis,IT administratorius,Sekretorius']
                    : ['prohibited']),
        ]);

        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'pedagogical_name' => $request->filled('pedagogical_name') 
                ? strtolower($request->input('pedagogical_name')) 
                : null,
            'gender' => $request->input('gender'),
            'role' => $request->input('role'),
        ]);

        return redirect()->route('users.panel');
    }


    /**
     * Update the specified user's password in storage.
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($request->input('password'))]);
        }

        return redirect()->route('users.panel');
    }

    public function dashboard(): View
    {
        $temp_bodies = Body::orderBy('title', 'asc')->get();
        $bodies = collect();
        foreach ($temp_bodies as $body) {
            if ($body->members->contains(Auth::user())) {
                $bodies->add($body);
            }
        }

        $meetings = collect();
        foreach ($bodies as $body) {
            foreach ($body->meetings as $meeting) {
                if ($meeting->status !== 'Baigtas') {
                    $meetings->add($meeting);
                }
            }
        }
        $meetings = $meetings->sortByDesc('meeting_date');
        
        return view('dashboard', ['bodies' => $bodies, 'meetings' => $meetings]);
    }
}