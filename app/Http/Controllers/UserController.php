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
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $perPage = in_array((int) request('perPage'), [10, 20, 50, 100]) ? (int) request('perPage') : 20;
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

        return view('users.index', compact('users'));
    }

    
    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {

        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        return view('users.edit', ['user' => $user]);
    }

    
    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Check if user is authenticated
        $authenticatedUser = auth()->user();
        if (!$authenticatedUser) {
            return redirect()->route('login');
        }
     
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }
        
        // You can add admin check here if needed:
        if ($authenticatedUser->role !== 'IT administratorius') {
            abort(403);
        }
        
        // Only require password confirmation for non-Microsoft-linked admin accounts
        if (empty($authenticatedUser->ms_id)) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }
        
        $user->delete();

        return redirect()->route('users.index');
    }

    
    /**
     * Update the specified user's profile in storage.
     */
    public function updateProfile(Request $request, User $user): RedirectResponse
    {
        $authenticatedUser = auth()->user();

        if (!$authenticatedUser) {
            return redirect()->route('login');
        }

        if (! $authenticatedUser->isPrivileged()) {
            abort(403);
        }

        $authRole = $authenticatedUser->role;
        $targetRole = $user->role;

        // Authorization logic:
        // - Users can update their own profile
        // - IT administrators can update anyone
        // - Secretaries can update only voters (Balsuojantysis)
        // - Everyone else forbidden to update other users
        if ($authenticatedUser->user_id !== $user->user_id) {
            if ($authRole === 'Sekretorius' && $targetRole !== 'Balsuojantysis') {
                abort(403);
            } elseif ($authRole !== 'IT administratorius' && $authRole !== 'Sekretorius') {
                abort(403);
            }
        }

        // Validation rules depend on authenticated user's role
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->user_id . ',user_id'],
            'pedagogical_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['integer', 'in:0,1'],
            'role' => $authRole === 'Sekretorius'
                ? ['string', 'in:Balsuojantysis,Sekretorius']
                : ($authRole === 'IT administratorius'
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

        return redirect()->route('users.index');
    }



    /**
     * Update the specified user's password in storage.
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $authenticatedUser = auth()->user();
        if (!$authenticatedUser) {
            return redirect()->route('login');
        }
        
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        // Prevent password updates for Microsoft-linked accounts
        if (!empty($user->ms_id)) {
            return redirect()->route('users.edit', $user)
                ->withErrors([
                    'password' => __('Password changes are not allowed for Microsoft-linked accounts.')
                ], 'updatePassword');
        }

        if ($authenticatedUser->id !== $user->id) {
            abort(403);
        }
        
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($request->input('password'))]);
        }

        return redirect()->route('users.index');
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