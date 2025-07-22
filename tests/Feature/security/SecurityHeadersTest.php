<?php

use App\Models\User;

it('sets content security policy header', function () {
    $response = $this->get(route('login'));
    
    $response->assertHeader('Content-Security-Policy');
    $csp = $response->headers->get('Content-Security-Policy');
    
    expect($csp)->toContain("default-src 'self'");
    expect($csp)->toContain("frame-ancestors 'none'");
});

it('sets x content type options header', function () {
    $response = $this->get(route('login'));
    
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('sets x frame options header', function () {
    $response = $this->get(route('login'));
    
    $response->assertHeader('X-Frame-Options', 'DENY');
});

it('sets x xss protection header', function () {
    $response = $this->get(route('login'));
    
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
});

it('sets referrer policy header', function () {
    $response = $this->get(route('login'));
    
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('sets permissions policy header', function () {
    $response = $this->get(route('login'));
    
    $response->assertHeader('Permissions-Policy');
    $permissionsPolicy = $response->headers->get('Permissions-Policy');
    
    expect($permissionsPolicy)->toContain('camera=()');
    expect($permissionsPolicy)->toContain('microphone=()');
    expect($permissionsPolicy)->toContain('geolocation=()');
});

it('applies security headers to authenticated routes', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->get(route('dashboard'));
    
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
});
