<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
        
        // If password change is forced, don't require current password
        if ($user->password_change_required) {
            $validated = $request->validateWithBag('updatePassword', [
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            ]);
        } else {
            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_change_required' => false, // Clear forced password change flag
        ]);

        return back()->with('status', 'password-updated');
    }
}
