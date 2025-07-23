<?php

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    Session::start();
    
    // Set locale to English for consistent test assertions
    app()->setLocale('en');
    
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'ms_id' => null, // Local account only
    ]);
    
    $this->google2fa = new Google2FA();
});

it('allows user to access 2fa setup page', function () {
    $response = $this->actingAs($this->user)
        ->get(route('two-factor.setup'));

    $response->assertStatus(200);
    $response->assertViewIs('two-factor.setup');
    $response->assertSee('Setup Two-Factor Authentication');
});

it('prevents microsoft users from accessing 2fa setup', function () {
    $microsoftUser = User::factory()->create([
        'ms_id' => 'microsoft-123',
    ]);

    $response = $this->actingAs($microsoftUser)
        ->get(route('two-factor.setup'));

    // Microsoft users should be blocked by middleware
    $response->assertStatus(403);
});

it('allows user to setup 2fa with valid code', function () {
    // Generate a secret for testing
    $secret = $this->google2fa->generateSecretKey();
    
    // Store secret in session (simulating setup process)
    Session::put('2fa_secret', $secret);
    
    // Generate valid TOTP code
    $validCode = $this->google2fa->getCurrentOtp($secret);

    $response = $this->actingAs($this->user)
        ->post(route('two-factor.confirm'), [
            'code' => $validCode,
        ]);

    $response->assertRedirect(route('two-factor.recovery-codes'));
    $response->assertSessionHas('status');

    // Verify user has 2FA enabled
    $this->user->refresh();
    expect($this->user->two_factor_secret)->not->toBeNull();
    expect($this->user->two_factor_confirmed_at)->not->toBeNull();
    expect($this->user->two_factor_recovery_codes)->not->toBeNull();

    // Verify audit log was created
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->user->user_id,
        'action' => '2fa_setup',
    ]);
});

it('prevents user from setting up 2fa with invalid code', function () {
    $secret = $this->google2fa->generateSecretKey();
    Session::put('2fa_secret', $secret);

    $response = $this->actingAs($this->user)
        ->post(route('two-factor.confirm'), [
            'code' => '000000', // Invalid code
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['code']);

    // Verify user doesn't have 2FA enabled
    $this->user->refresh();
    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
});

it('redirects user with 2fa to verification during login', function () {
    // Setup 2FA for user
    $secret = $this->google2fa->generateSecretKey();
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
    ]);

    $response = $this->post('/login', [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('two-factor.verify'));
    $this->assertGuest(); // User should not be logged in yet
});

it('allows user to login with valid 2fa code', function () {
    // Generate a proper 2FA secret using Google2FA
    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();
    
    // Enable 2FA for user
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => ['recovery123', 'recovery456'],
        'two_factor_confirmed_at' => now(),
    ]);

    // Login first
    $response = $this->post('/login', [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('two-factor.verify'));
    $this->assertGuest();

    // Generate valid TOTP code
    $validCode = $google2fa->getCurrentOtp($secret);

    // Verify with valid code
    $response = $this->post(route('two-factor.verify.post'), [
        'code' => $validCode,
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($this->user);

    // Verify audit log was created
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->user->user_id,
        'action' => 'login',
    ]);
});

it('prevents user from logging in with invalid 2fa code', function () {
    // Generate a proper 2FA secret using Google2FA
    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();
    
    // Enable 2FA for user
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => ['code1', 'code2'],
        'two_factor_confirmed_at' => now(),
    ]);

    // Login first to set up session
    $response = $this->post('/login', [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('two-factor.verify'));
    $this->assertGuest();

    // Verify session state is set up correctly
    expect(session('pending_2fa_user_id'))->toBe($this->user->user_id);

    $response = $this->post(route('two-factor.verify.post'), [
        'code' => '000000', // Invalid code
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['code']);
    $this->assertGuest();
});

it('allows user to login with recovery code', function () {
    // Generate a proper 2FA secret using Google2FA
    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();
    
    // Enable 2FA for user
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => ['ABCD1234', 'EFGH5678'],
        'two_factor_confirmed_at' => now(),
    ]);

    // Login first
    $response = $this->post('/login', [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('two-factor.verify'));
    $this->assertGuest();

    $response = $this->post(route('two-factor.verify.post'), [
        'code' => 'ABCD1234',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($this->user);

    // Verify recovery code was removed
    $this->user->refresh();
    $remainingCodes = $this->user->two_factor_recovery_codes;
    expect($remainingCodes)->not->toContain('ABCD1234');
    expect($remainingCodes)->toContain('EFGH5678');

});

it('allows user to disable 2fa', function () {
    // Setup 2FA for user
    $secret = $this->google2fa->generateSecretKey();
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('two-factor.disable'), [
            'password' => 'password123',
        ]);

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('status');

    // Verify 2FA was disabled
    $this->user->refresh();
    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
    expect($this->user->two_factor_recovery_codes)->toBeNull();

    // Verify audit log was created
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->user->user_id,
        'action' => '2fa_removed',
    ]);
});

it('allows user to regenerate recovery codes', function () {
    // Setup 2FA for user
    $secret = $this->google2fa->generateSecretKey();
    $originalCodes = ['old1', 'old2'];
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $originalCodes,
    ]);

    $response = $this->actingAs($this->user)
        ->post(route('two-factor.recovery-codes.regenerate'));

    $response->assertRedirect(route('two-factor.recovery-codes'));
    $response->assertSessionHas('status');

    // Verify recovery codes were regenerated
    $this->user->refresh();
    $newCodes = $this->user->two_factor_recovery_codes;
    expect($newCodes)->not->toEqual($originalCodes);
    expect($newCodes)->toHaveCount(8); // Should have 8 new codes
});

it('allows it admin to remove user 2fa', function () {
    // Create IT admin
    $admin = User::factory()->create([
        'role' => 'IT administratorius',
    ]);

    // Setup 2FA for target user
    $secret = $this->google2fa->generateSecretKey();
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['code1', 'code2'],
    ]);

    $response = $this->actingAs($admin)
        ->delete(route('users.remove-two-factor', $this->user->user_id));

    $response->assertRedirect();
    $response->assertSessionHas('status');

    // Verify 2FA was removed
    $this->user->refresh();
    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
    expect($this->user->two_factor_recovery_codes)->toBeNull();

    // Verify audit log was created
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->user->user_id,
        'action' => '2fa_removed',
    ]);
});

it('prevents regular user from removing other user 2fa', function () {
    $regularUser = User::factory()->create([
        'role' => 'Balsuojantysis',
    ]);

    // Setup 2FA for target user
    $secret = $this->google2fa->generateSecretKey();
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($regularUser)
        ->delete(route('users.remove-two-factor', $this->user->user_id));

    $response->assertStatus(403);

    // Verify 2FA was not removed
    $this->user->refresh();
    expect($this->user->two_factor_secret)->not->toBeNull();
});

it('applies rate limiting to 2fa verification', function () {
    // Setup 2FA for user
    $secret = $this->google2fa->generateSecretKey();
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    // Start login process
    $this->post('/login', [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    // Make multiple failed attempts
    for ($i = 0; $i < 6; $i++) {
        $this->post(route('two-factor.verify.post'), [
            'code' => '000000',
        ]);
    }

    // Next attempt should be rate limited
    $response = $this->post(route('two-factor.verify.post'), [
        'code' => '000000',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['code']);
    
    // Check that the error message mentions rate limiting
    $errors = session('errors');
    expect($errors->get('code')[0])->toContain('Too many 2FA attempts');
});

it('allows user without 2fa to login normally', function () {
    $response = $this->post('/login', [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($this->user);

    // Verify audit log was created
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->user->user_id,
        'action' => 'login',
    ]);
});

it('generates qr code for 2fa setup', function () {
    $response = $this->actingAs($this->user)
        ->get(route('two-factor.setup'));

    $response->assertStatus(200);
    expect($response->viewData('qrCodeUrl'))->toBeString();
    expect($response->viewData('secret'))->toBeString();
    
    // Verify QR code URL contains expected data
    $qrCodeUrl = $response->viewData('qrCodeUrl');
    expect($qrCodeUrl)->toContain('otpauth://totp/');
    expect($qrCodeUrl)->toContain(urlencode($this->user->email));
});

it('shows recovery codes page for authenticated user with 2fa', function () {
    // Setup 2FA for user
    $secret = $this->google2fa->generateSecretKey();
    $recoveryCodes = ['code1', 'code2', 'code3'];
    $this->user->update([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $recoveryCodes,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('two-factor.recovery-codes'));

    $response->assertStatus(200);
    $response->assertViewIs('two-factor.recovery-codes');
    expect($response->viewData('recoveryCodes'))->toEqual($recoveryCodes);
});

it('prevents user without 2fa from accessing recovery codes', function () {
    $response = $this->actingAs($this->user)
        ->get(route('two-factor.recovery-codes'));

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('error');
});
