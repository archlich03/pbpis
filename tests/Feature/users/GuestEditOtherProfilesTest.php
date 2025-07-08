<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prevents guests from users index', function () {
    $response = $this->get(route('users.index'));

    $response->assertRedirect(route('login'));
});

it('prevents guests from users edit page', function () {
    $user = User::factory()->create();
    
    $response = $this->get(route('users.edit', $user));

    $response->assertRedirect(route('login'));
});

it('prevents guests from updating other user profiles', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    
    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'pedagogical_name' => 'Updated Pedagogical Name',
        'role' => 'Sekretorius',
        'gender' => '1',
    ];
    
    $response = $this->withoutMiddleware()
        ->startSession()
        ->patch(route('users.updateProfile', $user), $updateData);

    $response->assertRedirect(route('login'));
    
    $this->assertDatabaseHas('users', [
        'user_id' => $user->user_id,
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    
    $this->assertDatabaseMissing('users', [
        'user_id' => $user->user_id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

it('prevents guests from updating other user passwords', function () {
    $user = User::factory()->create([
        'password' => Hash::make('original-password'),
    ]);
    
    $passwordData = [
        'password' => 'hacked-password',
        'password_confirmation' => 'hacked-password',
        '_method' => 'PATCH',
    ];
    
    $response = $this->withoutMiddleware()
        ->startSession()
        ->patch(route('users.updatePassword', $user), $passwordData);
    
    $response->assertRedirect(route('login'));
    
    // Verify the password wasn't changed
    $user->refresh();
    expect(Hash::check('original-password', $user->password))->toBeTrue();
    expect(Hash::check('hacked-password', $user->password))->toBeFalse();
});

it('prevents guests from deleting other users', function () {
    $user = User::factory()->create();
    
    $response = $this->withoutMiddleware()
        ->startSession()
        ->delete(route('users.destroy', $user));
    
    $response->assertRedirect(route('login'));
    
    // Verify the user still exists
    $this->assertDatabaseHas('users', [
        'user_id' => $user->user_id,
    ]);
});