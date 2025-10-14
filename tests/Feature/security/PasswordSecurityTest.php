<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    Session::start();
});

it('rejects passwords shorter than 12 characters', function () {
    $admin = User::factory()->create(['role' => 'IT administratorius']);
    
    $response = $this->actingAs($admin)
        ->withSession(['_token' => csrf_token()])
        ->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Short1!',
            'password_confirmation' => 'Short1!',
            'gender' => 0,
            'role' => 'Balsuojantysis',
            '_token' => csrf_token(),
        ]);
    
    $response->assertSessionHasErrors(['password']);
});

it('rejects passwords without uppercase letters', function () {
    $admin = User::factory()->create(['role' => 'IT administratorius']);
    
    $response = $this->actingAs($admin)
        ->withSession(['_token' => csrf_token()])
        ->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'lowercaseonly123!',
            'password_confirmation' => 'lowercaseonly123!',
            'gender' => 0,
            'role' => 'Balsuojantysis',
            '_token' => csrf_token(),
        ]);
    
    $response->assertSessionHasErrors(['password']);
});

it('rejects passwords without lowercase letters', function () {
    $admin = User::factory()->create(['role' => 'IT administratorius']);
    
    $response = $this->actingAs($admin)
        ->withSession(['_token' => csrf_token()])
        ->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'UPPERCASEONLY123!',
            'password_confirmation' => 'UPPERCASEONLY123!',
            'gender' => 0,
            'role' => 'Balsuojantysis',
            '_token' => csrf_token(),
        ]);
    
    $response->assertSessionHasErrors(['password']);
});

it('rejects passwords without numbers', function () {
    $admin = User::factory()->create(['role' => 'IT administratorius']);
    
    $response = $this->actingAs($admin)
        ->withSession(['_token' => csrf_token()])
        ->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'NoNumbersHere!',
            'password_confirmation' => 'NoNumbersHere!',
            'gender' => 0,
            'role' => 'Balsuojantysis',
            '_token' => csrf_token(),
        ]);
    
    $response->assertSessionHasErrors(['password']);
});

it('rejects passwords without symbols', function () {
    $admin = User::factory()->create(['role' => 'IT administratorius']);
    
    $response = $this->actingAs($admin)
        ->withSession(['_token' => csrf_token()])
        ->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'NoSymbolsHere123',
            'password_confirmation' => 'NoSymbolsHere123',
            'gender' => 0,
            'role' => 'Balsuojantysis',
            '_token' => csrf_token(),
        ]);
    
    $response->assertSessionHasErrors(['password']);
});

it('accepts strong passwords', function () {
    $admin = User::factory()->create(['role' => 'IT administratorius']);
    
    // Use a unique strong password that won't be in breach databases
    $uniquePassword = 'Xk9#mP2$vL8@qW5!rT3^yN7&';
    
    $response = $this->actingAs($admin)
        ->withSession(['_token' => csrf_token()])
        ->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $uniquePassword,
            'password_confirmation' => $uniquePassword,
            'gender' => 0,
            'role' => 'Balsuojantysis',
            '_token' => csrf_token(),
        ]);
    
    $response->assertRedirect('/users');
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

it('enforces strong passwords on password update', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
        'ms_id' => null, // Ensure no Microsoft ID to allow password updates
    ]);
    
    $response = $this->actingAs($user)
        ->withSession(['_token' => csrf_token()])
        ->put('/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'weak',
            'password_confirmation' => 'weak',
            '_token' => csrf_token(),
        ]);
    
    // The password should be rejected due to weak password rules
    $response->assertSessionHasErrorsIn('updatePassword', ['password']);
});

it('allows strong password updates', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
        'ms_id' => null, // Ensure no Microsoft ID to allow password updates
    ]);
    
    $response = $this->actingAs($user)
        ->withSession(['_token' => csrf_token()])
        ->put('/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewStrongPassword456@',
            'password_confirmation' => 'NewStrongPassword456@',
            '_token' => csrf_token(),
        ]);
    
    $response->assertRedirect();
    $user->refresh();
    expect(Hash::check('NewStrongPassword456@', $user->password))->toBeTrue();
});
