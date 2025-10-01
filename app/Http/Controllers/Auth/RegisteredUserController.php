<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::min(12)->mixedCase()->numbers()->symbols()],
            'gender' => ['required', 'boolean'],
            'role' => ['required', 'string', 'max:32'],
            'pedagogical_name' => ['nullable', 'string', 'max:32'],
        ]);

        // Auto-detect gender if not provided or use provided value
        $detectedGender = User::detectGenderFromLithuanianName($request->name);
        $finalGender = $request->filled('gender') ? $request->gender : $detectedGender;
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'gender' => $finalGender,
            'role' => $request->role,
            'pedagogical_name' => $request->filled('pedagogical_name') 
                ? strtolower($request->input('pedagogical_name')) 
                : null,
        ]);

        event(new Registered($user));

        // Log the user creation
        $createdBy = Auth::user();
        AuditLog::log(
            $user->user_id,
            'user_created',
            $request->ip(),
            $request->userAgent(),
            [
                'created_by' => $createdBy->user_id,
                'created_by_name' => $createdBy->name,
                'created_by_role' => $createdBy->role,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->role,
            ]
        );

        return redirect(route('users.index', absolute: false));
    }
}
