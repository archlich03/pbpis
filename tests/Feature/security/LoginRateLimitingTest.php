<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    // Clear any existing rate limits before each test
    RateLimiter::clear('login:test@example.com|127.0.0.1');
    RateLimiter::clear('microsoft-auth:127.0.0.1');
    $this->withSession(['_token' => 'test_token']);
});

it('allows up to 10 failed login attempts before rate limiting', function () {
    // Create a user
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    // Make 10 failed attempts - should not be rate limited yet
    for ($i = 0; $i < 10; $i++) {
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            '_token' => 'test_token',
        ]);
        
        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();
        
        // Should show regular auth failed message, not rate limiting
        expect($response->getSession()->get('errors')->get('email')[0])
            ->toContain('Autentifikacija nepavyko');
    }

    // 11th attempt should be rate limited
    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
        '_token' => 'test_token',
    ]);
    
    $response->assertSessionHasErrors(['email']);
    expect($response->getSession()->get('errors')->get('email')[0])
        ->toContain('Per daug prisijungimo bandymų');
});

it('rate limiting blocks even correct passwords until timeout expires', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    // Make 10 failed attempts to trigger rate limiting
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            '_token' => 'test_token',
        ]);
    }

    // Verify we're rate limited with wrong password
    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
        '_token' => 'test_token',
    ]);
    
    expect($response->getSession()->get('errors')->get('email')[0])
        ->toContain('Per daug prisijungimo bandymų');

    // Even correct credentials should be blocked when rate limited
    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'correct-password',
        '_token' => 'test_token',
    ]);
    
    // Should still be rate limited, not redirected to dashboard
    $response->assertSessionHasErrors(['email']);
    expect($response->getSession()->get('errors')->get('email')[0])
        ->toContain('Per daug prisijungimo bandymų');
});

it('applies rate limiting to microsoft authentication attempts', function () {
    // Simulate 10 failed Microsoft auth attempts
    $throttleKey = 'microsoft-auth:127.0.0.1';
    
    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit($throttleKey, 1800);
    }
    
    // Next attempt should be rate limited
    $response = $this->get(route('login.microsoft.callback') . '?error=access_denied&error_description=Test+error');
    
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('Per daug Microsoft autentifikacijos bandymų');
});

it('rate limiting uses correct time windows', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    // Make 10 failed attempts
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            '_token' => 'test_token',
        ]);
    }

    // Should be rate limited
    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
        '_token' => 'test_token',
    ]);
    
    $errorMessage = $response->getSession()->get('errors')->get('email')[0];
    expect($errorMessage)->toContain('Per daug prisijungimo bandymų');
    expect($errorMessage)->toContain('minučių'); // Should show minutes remaining
});

it('rate limiting is applied per email and IP combination', function () {
    $user1 = User::factory()->create([
        'email' => 'user1@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $user2 = User::factory()->create([
        'email' => 'user2@example.com',
        'password' => bcrypt('password'),
    ]);

    // Make 10 failed attempts for user1
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('login'), [
            'email' => 'user1@example.com',
            'password' => 'wrong-password',
            '_token' => 'test_token',
        ]);
    }

    // user1 should be rate limited
    $response = $this->post(route('login'), [
        'email' => 'user1@example.com',
        'password' => 'wrong-password',
        '_token' => 'test_token',
    ]);
    
    expect($response->getSession()->get('errors')->get('email')[0])
        ->toContain('Per daug prisijungimo bandymų');

    // user2 should not be rate limited (different email)
    $response = $this->post(route('login'), [
        'email' => 'user2@example.com',
        'password' => 'wrong-password',
        '_token' => 'test_token',
    ]);
    
    $response->assertSessionHasErrors(['email']);
    expect($response->getSession()->get('errors')->get('email')[0])
        ->not->toContain('Too many login attempts');
});
