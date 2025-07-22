<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy - restrict resource loading
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval'";
        $csp .= app()->environment('local') ? " localhost:5173 127.0.0.1:5173" : "";
        $csp .= "; ";
        $csp .= "style-src 'self' 'unsafe-inline' fonts.bunny.net";
        $csp .= app()->environment('local') ? " localhost:5173 127.0.0.1:5173" : "";
        $csp .= "; ";
        $csp .= "img-src 'self' data: https:; ";
        $csp .= "font-src 'self' fonts.bunny.net; ";
        $csp .= "connect-src 'self'";
        $csp .= app()->environment('local') ? " localhost:5173 127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173" : "";
        $csp .= "; ";
        $csp .= "frame-ancestors 'none';";
        
        $response->headers->set('Content-Security-Policy', $csp);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Enable XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Force HTTPS (only if in production and using HTTPS)
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Prevent information disclosure
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Feature policy / permissions policy
        $response->headers->set('Permissions-Policy', 
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        return $response;
    }
}
