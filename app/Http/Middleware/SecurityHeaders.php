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

        // Content Security Policy
        $appUrl = config('app.url');
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'" . (app()->environment('local') ? ' http://localhost:5173' : ''), // Allow inline scripts for Alpine.js and Vite
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net" . (app()->environment('local') ? ' http://localhost:5173' : ''), // Allow inline styles for Tailwind and external fonts
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.bunny.net",
            "connect-src 'self' ws: wss:" . (app()->environment('local') ? ' ws://localhost:* wss://localhost:* http://localhost:*' : ''), // Allow WebSocket for Vite HMR
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self' " . $appUrl, // Allow forms to be submitted to the configured APP_URL
        ];
        
        $csp = implode('; ', $cspDirectives);
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
