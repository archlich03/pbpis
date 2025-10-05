<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [
                'required', 
                'confirmed', 
                Rules\Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            ],
        ], [
            'password.min' => __('The password must be at least :min characters.', ['min' => 12]),
            'password.mixed' => __('The password must contain both uppercase and lowercase letters.'),
            'password.numbers' => __('The password must contain at least one number.'),
            'password.symbols' => __('The password must contain at least one special character (including Lithuanian characters: ąčęėįšųūž).'),
            'password.uncompromised' => __('The password has appeared in a data leak. Please choose a different password.'),
            'password.confirmed' => __('The password confirmation does not match.'),
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                
                // Log the password reset
                AuditLog::log(
                    $user->user_id,
                    'password_changed',
                    $request->ip(),
                    $request->userAgent(),
                    [
                        'via_reset_link' => true,
                    ]
                );
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
