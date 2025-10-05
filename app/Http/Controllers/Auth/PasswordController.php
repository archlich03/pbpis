<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Prevent password updates for Microsoft-linked accounts
        if (!empty($user->ms_id)) {
            return back()->withErrors([
                'password' => __('Password changes are not allowed for Microsoft-linked accounts.')
            ], 'updatePassword');
        }
        
        // Custom error messages for password validation
        $messages = [
            'current_password.current_password' => __('The current password is incorrect.'),
            'password.min' => __('The password must be at least :min characters.', ['min' => 12]),
            'password.mixed' => __('The password must contain both uppercase and lowercase letters.'),
            'password.numbers' => __('The password must contain at least one number.'),
            'password.symbols' => __('The password must contain at least one special character (including Lithuanian characters: ąčęėįšųūž).'),
            'password.uncompromised' => __('The password has appeared in a data leak. Please choose a different password.'),
            'password.confirmed' => __('The password confirmation does not match.'),
        ];
        
        // If password change is forced, don't require current password
        if ($user->password_change_required) {
            $validated = $request->validateWithBag('updatePassword', [
                'password' => [
                    'required', 
                    'confirmed', 
                    Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
                ],
            ], $messages);
        } else {
            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => ['required', 'current_password'],
                'password' => [
                    'required', 
                    'confirmed', 
                    Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
                ],
            ], $messages);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_change_required' => false, // Clear forced password change flag
        ]);

        // Log the password change
        AuditLog::log(
            $user->user_id,
            'password_changed',
            $request->ip(),
            $request->userAgent(),
            [
                'self_changed' => true,
                'was_forced' => $user->password_change_required,
            ]
        );

        return back()->with('status', 'password-updated');
    }
}
