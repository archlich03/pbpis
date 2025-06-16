<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetLocale; // Import your custom middleware

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Append your SetLocale middleware to the 'web' group.
        // This ensures it runs after Laravel's core session and cookie middleware
        // have done their job, making the session available.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // If you had any global middleware or route middleware aliases,
        // they would go here, for example:
        // $middleware->global([
        //     // \App\Http\Middleware\TrustProxies::class,
        // ]);
        //
        // $middleware->alias([
        //     'auth' => \App\Http\Middleware\Authenticate::class,
        //     'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        //     'role' => \App\Http\Middleware\CheckUserRole::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ...
    })->create();
