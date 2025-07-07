<?php

use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

it('allows a user to update their own profile', function () {
    $user = User::factory()->create();
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
        'password' => Hash::make('old-password'),
    ]);
    $this->actingAs($user);

    $passwordData = [
        'current_password' => 'old-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ];

    $response = $this->put(route('password.update'), $passwordData);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'password-updated');

    $user->refresh();
    expect(Hash::check('new-password', $user->password))->toBeTrue();
});

it('allows a user to delete their own profile', function () {
    $user = User::factory()->create([
        'password' => bcrypt('123456789'),
    ]);

    $this->actingAs($user)
         ->startSession()  // Just add this line
         ->delete(route('profile.destroy'), [
             'password' => '123456789',
         ])
         ->assertRedirect('/');

    $this->assertDatabaseMissing('users', [
        'user_id' => $user->user_id,
    ]);
});