<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $users = User::orderBy('name', 'asc')->get(); // Fetch all users sorted by name

        return view('users-panel', ['users' => $users]); // Pass users to the view
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
}