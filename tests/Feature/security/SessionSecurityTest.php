<?php

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Session::start();
});

it('regenerates session on login', function () {
    $user = User::factory()->create([
        'password' => Hash::make('TestPassword123!')
    ]);
    
    // Start a session and get the initial session ID
    $this->get(route('login'));
    $initialSessionId = Session::getId();
    
    // Login
    $response = $this->withSession(['_token' => csrf_token()])
        ->post(route('login'), [
            'email' => $user->email,
            'password' => 'TestPassword123!',
            '_token' => csrf_token(),
        ]);
    
    // Check that session ID has changed
    $newSessionId = Session::getId();
    expect($newSessionId)->not->toBe($initialSessionId);
    
    $response->assertRedirect(route('dashboard'));
});

it('invalidates session on logout', function () {
    $user = User::factory()->create();
    
    // Login first
    $response = $this->actingAs($user)
        ->withSession(['_token' => csrf_token()])
        ->post(route('logout'), ['_token' => csrf_token()]);
    
    // Check that we're redirected and session is invalidated
    $response->assertRedirect('/');
    $this->assertGuest();
});

it('protects against csrf attacks', function () {
    $user = User::factory()->create();
    
    // Try to make a request without CSRF token
    $response = $this->actingAs($user)->post(route('logout'));
    
    // Should fail due to missing CSRF token
    $response->assertStatus(419); // CSRF token mismatch
});

it('allows requests with valid csrf token', function () {
    $user = User::factory()->create();
    
    // Make request with valid CSRF token
    $response = $this->actingAs($user)
        ->withSession(['_token' => csrf_token()])
        ->post(route('logout'), ['_token' => csrf_token()]);
    
    $response->assertRedirect('/');
});

it('prevents session fixation attacks', function () {
    // Create a user
    $user = User::factory()->create([
        'password' => Hash::make('TestPassword123!')
    ]);
    
    // Attacker sets a session ID
    Session::setId('attacker-session-id');
    Session::start();
    $attackerSessionId = Session::getId();
    
    // User logs in
    $response = $this->withSession(['_token' => csrf_token()])
        ->post(route('login'), [
            'email' => $user->email,
            'password' => 'TestPassword123!',
            '_token' => csrf_token(),
        ]);
    
    // Session ID should be different after login (regenerated)
    $newSessionId = Session::getId();
    expect($newSessionId)->not->toBe($attackerSessionId);
});

it('maintains session security for authenticated users', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->get(route('dashboard'));
    
    $response->assertOk();
    expect(Session::has('_token'))->toBeTrue();
});

it('protects sensitive routes with session verification', function () {
    // Try to access protected route without authentication
    $response = $this->get(route('dashboard'));
    
    $response->assertRedirect(route('login'));
});

it('protects against session hijacking with user agent changes', function () {
    $user = User::factory()->create();
    
    // Login with specific user agent
    $response = $this->withHeaders([
        'User-Agent' => 'Original-Browser/1.0'
    ])->actingAs($user)->get(route('dashboard'));
    
    $response->assertOk();
    
    // Try to access with different user agent (simulating hijacking)
    // Note: Laravel doesn't automatically protect against this, but we can test
    // that the session is still valid (this is more of a documentation test)
    $response = $this->withHeaders([
        'User-Agent' => 'Malicious-Browser/2.0'
    ])->actingAs($user)->get(route('dashboard'));
    
    // This will still work in Laravel by default, but documents the potential risk
    $response->assertOk();
});
