<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChangeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Skip if user is not authenticated
        if (!$user) {
            return $next($request);
        }
        
        // Skip for Microsoft accounts (they can't change passwords)
        if (!empty($user->ms_id)) {
            return $next($request);
        }
        
        // Skip if password change is not required
        if (!$user->password_change_required) {
            return $next($request);
        }
        
        // Allow access to password change routes and logout
        $allowedRoutes = [
            'password.update',
            'profile.edit',
            'logout',
            'force-password-change',
        ];
        
        if (in_array($request->route()->getName(), $allowedRoutes)) {
            return $next($request);
        }
        
        // Redirect to forced password change page
        return redirect()->route('force-password-change')
            ->with('message', 'You must change your password before continuing.');
    }
}
