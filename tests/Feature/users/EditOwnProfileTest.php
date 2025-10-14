<?php

use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

it('allows a user to update their own profile', function () {
    // Create user with IT admin role so they can change roles
    $user = User::factory()->create([
        'role' => 'IT administratorius'
    ]);
    $this->actingAs($user);

    $updatedData = [
        'name' => 'Updated Name',
        'email' => 'updatedemail@example.com',
        'pedagogical_name' => 'Updated Pedagogical Name',
        'role' => 'Sekretorius',
        'gender' => '1',
        '_method' => 'PATCH',
    ];

    $response = $this->patch(route('profile.update'), $updatedData);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'profile-updated');

    $this->assertDatabaseHas('users', [
        'user_id' => $user->user_id,
        'name' => 'Updated Name',
        'email' => 'updatedemail@example.com',
        'pedagogical_name' => 'Updated Pedagogical Name',
        'role' => 'Sekretorius',
        'gender' => 1,
    ]);
});

it('allows a user to change their own password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);
    $this->actingAs($user);

    $passwordData = [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword456@',
        'password_confirmation' => 'NewPassword456@',
    ];

    $response = $this->put(route('password.update'), $passwordData);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'password-updated');

    $user->refresh();
    expect(Hash::check('NewPassword456@', $user->password))->toBeTrue();
});

it('prevents users from deleting their own profile', function () {
    $user = User::factory()->create([
        'password' => bcrypt('123456789'),
    ]);

    $this->actingAs($user)
         ->startSession()
         ->delete(route('profile.destroy'), [
             'password' => '123456789',
         ])
         ->assertForbidden();

    // User should still exist
    $this->assertDatabaseHas('users', [
        'user_id' => $user->user_id,
    ]);
});