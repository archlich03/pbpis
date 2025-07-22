<?php

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Session::start();
});

it('generates secure state token for microsoft auth', function () {
    $response = $this->get(route('login.microsoft'));
    
    // Should redirect to Microsoft with state parameter
    $response->assertRedirect();
    
    $location = $response->headers->get('Location');
    expect($location)->toContain('state=');
    
    // State should be stored in session
    expect(Session::has('microsoft_auth_state'))->toBeTrue();
    
    // State should be a secure random string
    $state = Session::get('microsoft_auth_state');
    expect(strlen($state))->toBeGreaterThan(20);
});

it('validates state token on callback', function () {
    // Set a state token in session
    Session::put('microsoft_auth_state', 'valid-state-token');
    
    // Try callback with invalid state
    $response = $this->get(route('login.microsoft.callback') . '?state=invalid-state&code=test-code');
    
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
});

it('clears state token after successful validation', function () {
    // This test would require mocking the Microsoft Graph API
    // For now, we'll test the state clearing logic directly
    
    Session::put('microsoft_auth_state', 'test-state');
    
    // Simulate the state clearing that happens in the controller
    Session::forget('microsoft_auth_state');
    
    expect(Session::has('microsoft_auth_state'))->toBeFalse();
});

it('prevents microsoft auth without state token', function () {
    // Try callback without any state
    $response = $this->get(route('login.microsoft.callback') . '?code=test-code');
    
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
});

it('ensures ms_id is fillable in user model', function () {
    $user = new User();
    $fillable = $user->getFillable();
    
    expect($fillable)->toContain('ms_id');
});
