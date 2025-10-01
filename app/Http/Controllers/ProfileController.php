<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditLog;
use App\Models\Body;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        /*if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }*/

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Only require password confirmation for non-Microsoft-linked accounts
        if (empty($user->ms_id)) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);
        }

        // Check if user is a chairman of any body
        $chairmanBodies = Body::where('chairman_id', $user->user_id)->get();
        if ($chairmanBodies->count() > 0) {
            return Redirect::route('profile.edit')
                ->withErrors([
                    'delete' => __('Cannot delete your account because you are the chairman of :count body/bodies: :bodies', [
                        'count' => $chairmanBodies->count(),
                        'bodies' => $chairmanBodies->pluck('title')->join(', ')
                    ])
                ], 'userDeletion');
        }
        
        // Check if user is a secretary of any meeting
        $secretaryMeetings = \App\Models\Meeting::where('secretary_id', $user->user_id)->get();
        if ($secretaryMeetings->count() > 0) {
            return Redirect::route('profile.edit')
                ->withErrors([
                    'delete' => __('Cannot delete your account because you are the secretary of :count meeting(s). Please contact an administrator.', [
                        'count' => $secretaryMeetings->count()
                    ])
                ], 'userDeletion');
        }

        // Store user info in audit logs before deletion
        \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('user_id', $user->user_id)
            ->update([
                'deleted_user_name' => $user->name,
                'deleted_user_email' => $user->email,
            ]);
        
        // Remove user from body members JSON arrays
        $bodies = Body::all();
        foreach ($bodies as $body) {
            $memberIds = json_decode($body->getRawOriginal('members') ?? '[]', true);
            
            if (in_array($user->user_id, $memberIds)) {
                $memberIds = array_values(array_filter($memberIds, fn($id) => $id != $user->user_id));
                $body->update(['members' => $memberIds]);
            }
        }
        
        // Log the self-deletion
        AuditLog::log(
            $user->user_id,
            'account_deleted',
            $request->ip(),
            $request->userAgent(),
            [
                'self_deleted' => true,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->role,
            ]
        );

        Auth::logout();

        $user->delete();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return Redirect::to('/');
    }
}
