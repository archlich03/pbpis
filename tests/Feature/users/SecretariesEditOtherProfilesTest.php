<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\User;

it('allows secretaries accessing users index', function () {
    app()->setLocale('en');
    $user = User::factory()->create([
        'role' => 'Sekretorius',
    ]);

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertSee(__('List of all users'));
});

it('allows secretaries accessing users edit page', function () {
    app()->setLocale('en');
    $targetUser = User::factory()->create();
    $actingUser = User::factory()->create([
        'role' => 'Sekretorius',
    ]);

    $response = $this->actingAs($actingUser)->get(route('users.edit', $targetUser));

    $response->assertSee(__('Edit user'));
});

it('allows secretaries updating other user profiles', function () {
    Session::start();

    $targetUser = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $actingUser = User::factory()->create([
        'role' => 'Sekretorius',
    ]);

    $updateData = [
        '_token' => csrf_token(),
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'pedagogical_name' => 'Updated Pedagogical Name',
        'role' => 'Balsuojantysis',
        'gender' => '1',
    ];

    $response = $this
        ->actingAs($actingUser)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $targetUser), $updateData);


    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', [
        'user_id' => $targetUser->user_id,
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $this->assertDatabaseHas('users', [
        'user_id' => $targetUser->user_id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    Session::flush();
    Session::invalidate();
});

it('allows secretaries updating other user passwords', function () {
    Session::start();

    $targetUser = User::factory()->create([
        'password' => Hash::make('OriginalPassword123!'),
    ]);

    $actingUser = User::factory()->create([
        'role' => 'Sekretorius',
    ]);

    $passwordData = [
        '_token' => csrf_token(),
        'password' => 'HackedPassword456@',
        'password_confirmation' => 'HackedPassword456@',
    ];

    $response = $this
        ->actingAs($actingUser)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updatePassword', $targetUser), $passwordData);

    $response->assertRedirect(route('users.index'));

    $targetUser->refresh();
    expect(Hash::check('OriginalPassword123!', $targetUser->password))->toBeFalse();
    expect(Hash::check('HackedPassword456@', $targetUser->password))->toBeTrue();

    Session::flush();
    Session::invalidate();
});


it('prevents secretaries from deleting other users', function () {
    Session::start();

    $targetUser = User::factory()->create();

    $actingUser = User::factory()->create([
        'role' => 'Sekretorius',
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

it('allows secretaries updating a voters profile', function () {
    Session::start();

    $secretary = User::factory()->create(['role' => 'Sekretorius']);
    $voter = User::factory()->create([
        'role' => 'Balsuojantysis',
        'name' => 'Original Voter',
        'email' => 'voter@example.com',
    ]);

    $updateData = [
        '_token' => csrf_token(),
        'name' => 'Hacked Voter',
        'email' => 'hackedvoter@example.com',
        'pedagogical_name' => 'Hacked Pedagogical',
        'role' => 'Balsuojantysis',
        'gender' => '1',
    ];

    $response = $this->actingAs($secretary)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $voter), $updateData);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', [
        'user_id' => $voter->user_id,
        'name' => 'Original Voter',
        'email' => 'voter@example.com',
        'role' => 'Balsuojantysis',
    ]);

    $this->assertDatabaseHas('users', [
        'user_id' => $voter->user_id,
        'name' => 'Hacked Voter',
        'email' => 'hackedvoter@example.com',
        'role' => 'Balsuojantysis',
    ]);

    Session::flush();
    Session::invalidate();
});

it('prevents secretary from updating another secretary profile', function () {
    Session::start();

    $secretary1 = User::factory()->create(['role' => 'Sekretorius']);
    $secretary2 = User::factory()->create([
        'role' => 'Sekretorius',
        'name' => 'Original Secretary',
        'email' => 'secretary@example.com',
    ]);

    $updateData = [
        '_token' => csrf_token(),
        'name' => 'Hacked Secretary',
        'email' => 'hackedsecretary@example.com',
        'pedagogical_name' => 'Hacked Pedagogical',
        'role' => 'Sekretorius',
        'gender' => '1',
    ];

    $response = $this->actingAs($secretary1)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $secretary2), $updateData);

    $response->assertForbidden();

    $this->assertDatabaseHas('users', [
        'user_id' => $secretary2->user_id,
        'name' => 'Original Secretary',
        'email' => 'secretary@example.com',
        'role' => 'Sekretorius',
    ]);

    Session::flush();
    Session::invalidate();
});

it('prevents secretary from updating an IT admin profile', function () {
    Session::start();

    $secretary = User::factory()->create(['role' => 'Sekretorius']);
    $admin = User::factory()->create([
        'role' => 'IT administratorius',
        'name' => 'Original Admin',
        'email' => 'admin@example.com',
    ]);

    $updateData = [
        '_token' => csrf_token(),
        'name' => 'Hacked Admin',
        'email' => 'hackedadmin@example.com',
        'pedagogical_name' => 'Hacked Pedagogical',
        'role' => 'IT administratorius',
        'gender' => '1',
    ];

    $response = $this->actingAs($secretary)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $admin), $updateData);

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
