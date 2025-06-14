<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string ...$roles  // <--- IMPORTANT: This accepts the roles passed from the route
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) { return redirect()->route('login'); }
        $user = Auth::user();

        if (!empty($roles) && !in_array($user->role, $roles)) {
            abort(403, 'Unauthorized');
        }
        return $next($request);
    }

}