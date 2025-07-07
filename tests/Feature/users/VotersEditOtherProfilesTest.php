<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\User;

it('prevents voters from accessing users index', function () {
    $user = User::factory()->create([
        'role' => 'Balsuojantysis',
    ]);

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertForbidden();
});

it('prevents voters from accessing user edit page', function () {
    $targetUser = User::factory()->create();
    $actingUser = User::factory()->create([
        'role' => 'Balsuojantysis',
    ]);

    $response = $this->actingAs($actingUser)->get(route('users.edit', $targetUser));

    $response->assertForbidden();
});

it('prevents voters from updating other user profiles', function () {
    Session::start();

    $targetUser = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $actingUser = User::factory()->create([
        'role' => 'Balsuojantysis',
    ]);

    $updateData = [
        '_token' => csrf_token(),
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'pedagogical_name' => 'Updated Pedagogical Name',
        'role' => 'Sekretorius',
        'gender' => '1',
    ];

    $response = $this
        ->actingAs($actingUser)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $targetUser), $updateData);

    $response->assertForbidden();

    // Ensure original values are unchanged
    $this->assertDatabaseHas('users', [
        'user_id' => $targetUser->user_id,
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $this->assertDatabaseMissing('users', [
        'user_id' => $targetUser->user_id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    Session::flush();
    Session::invalidate();
});

it('prevents voters from updating other user passwords', function () {
    Session::start();

    $targetUser = User::factory()->create([
        'password' => Hash::make('original-password'),
    ]);

    $actingUser = User::factory()->create([
        'role' => 'Balsuojantysis',
    ]);

    $passwordData = [
        '_token' => csrf_token(),
        'password' => 'hacked-password',
        'password_confirmation' => 'hacked-password',
    ];

    $response = $this
        ->actingAs($actingUser)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updatePassword', $targetUser), $passwordData);

    $response->assertForbidden();

    $targetUser->refresh();
    expect(Hash::check('original-password', $targetUser->password))->toBeTrue();
    expect(Hash::check('hacked-password', $targetUser->password))->toBeFalse();

    Session::flush();
    Session::invalidate();
});


it('prevents voters from deleting other users', function () {
    Session::start();

    $targetUser = User::factory()->create();

    $actingUser = User::factory()->create([
        'role' => 'Balsuojantysis',
    ]);

    $response = $this
        ->actingAs($actingUser)
        ->withSession(['_token' => csrf_token()])
        ->delete(route('users.destroy', $targetUser), [
            '_token' => csrf_token(),
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('users', [
        'user_id' => $targetUser->user_id,
    ]);

    Session::flush();
    Session::invalidate();
});

it('prevents voters from updating secretary profile', function () {
    Session::start();

    $balsuojantysis = User::factory()->create(['role' => 'Balsuojantysis']);
    $sekretorius = User::factory()->create([
        'role' => 'Sekretorius',
        'name' => 'Original Secretary',
        'email' => 'secretary@example.com',
    ]);

    $updatedData = [
        '_token' => csrf_token(),
        'name' => 'Hacked Secretary',
        'email' => 'hacked@example.com',
        'pedagogical_name' => 'Hacked Pedagogical Name',
        'role' => 'Balsuojantysis',
        'gender' => '1',
    ];

    $response = $this->actingAs($balsuojantysis)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $sekretorius), $updatedData);

    $response->assertForbidden();

    $this->assertDatabaseHas('users', [
        'user_id' => $sekretorius->user_id,
        'name' => 'Original Secretary',
        'email' => 'secretary@example.com',
        'role' => 'Sekretorius',
    ]);

    Session::flush();
    Session::invalidate();
});

it('prevents voters from updating IT admin profile', function () {
    Session::start();

    $balsuojantysis = User::factory()->create(['role' => 'Balsuojantysis']);
    $admin = User::factory()->create([
        'role' => 'IT administratorius',
        'name' => 'Original Admin',
        'email' => 'admin@example.com',
    ]);

    $updatedData = [
        '_token' => csrf_token(),
        'name' => 'Hacked Admin',
        'email' => 'hacked@example.com',
        'pedagogical_name' => 'Hacked Pedagogical Name',
        'role' => 'Balsuojantysis',
        'gender' => '1',
    ];

    $response = $this->actingAs($balsuojantysis)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $admin), $updatedData);

    $response->assertForbidden();

    $this->assertDatabaseHas('users', [
        'user_id' => $admin->user_id,
        'name' => 'Original Admin',
        'email' => 'admin@example.com',
        'role' => 'IT administratorius',
    ]);

    Session::flush();
    Session::invalidate();
});

it('prevents voters from deleting secretary profile', function () {
    Session::start();

    $balsuojantysis = User::factory()->create(['role' => 'Balsuojantysis']);
    $sekretorius = User::factory()->create(['role' => 'Sekretorius']);

    $response = $this->actingAs($balsuojantysis)
        ->withSession(['_token' => csrf_token()])
        ->delete(route('users.destroy', $sekretorius), [
            '_token' => csrf_token(),
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('users', [
        'user_id' => $sekretorius->user_id,
    ]);

    Session::flush();
    Session::invalidate();
});

it('prevents voters from deleting IT admin profile', function () {
    Session::start();

    $balsuojantysis = User::factory()->create(['role' => 'Balsuojantysis']);
    $admin = User::factory()->create(['role' => 'IT administratorius']);

    $response = $this->actingAs($balsuojantysis)
        ->withSession(['_token' => csrf_token()])
        ->delete(route('users.destroy', $admin), [
            '_token' => csrf_token(),
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('users', [
        'user_id' => $admin->user_id,
    ]);

    Session::flush();
    Session::invalidate();
});
