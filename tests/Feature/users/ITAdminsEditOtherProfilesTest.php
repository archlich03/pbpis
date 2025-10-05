<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\User;

it('allows IT admins accessing users index', function () {
    app()->setLocale('en');
    $user = User::factory()->create([
        'role' => 'IT administratorius',
    ]);

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertSee(__('List of all users'));
});

it('allows IT admins accessing users edit page', function () {
    app()->setLocale('en');
    $targetUser = User::factory()->create();
    $actingUser = User::factory()->create([
        'role' => 'Sekretorius',
    ]);

    $response = $this->actingAs($actingUser)->get(route('users.edit', $targetUser));

    $response->assertSee(__('Edit user'));
});

it('allows IT admins updating other user profiles', function () {
    Session::start();

    $targetUser = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $actingUser = User::factory()->create([
        'role' => 'IT administratorius',
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

it('allows IT admins updating other user passwords', function () {
    Session::start();

    $targetUser = User::factory()->create([
        'password' => Hash::make('OriginalPassword123!'),
    ]);

    $actingUser = User::factory()->create([
        'role' => 'IT administratorius',
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

    $response->assertRedirect(route('users.edit', $targetUser));

    $targetUser->refresh();
    expect(Hash::check('OriginalPassword123!', $targetUser->password))->toBeFalse();
    expect(Hash::check('HackedPassword456@', $targetUser->password))->toBeTrue();
    Session::flush();
    Session::invalidate();
});


it('allows IT admins deleting other users', function () {
    Session::start();

    $targetUser = User::factory()->create();

    // Create admin user with Microsoft account to bypass password validation
    $actingUser = User::factory()->create([
        'role' => 'IT administratorius',
        'ms_id' => 'test-microsoft-id-123',
    ]);

    $response = $this
        ->actingAs($actingUser)
        ->withSession(['_token' => csrf_token()])
        ->delete(route('users.destroy', $targetUser), [
            '_token' => csrf_token(),
        ]);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', [
        'user_id' => $targetUser->user_id,
    ]);
    Session::flush();
    Session::invalidate();
});

it('allows IT admin to update a voter profile', function () {
    Session::start();

    $itAdmin = User::factory()->create(['role' => 'IT administratorius']);
    $voter = User::factory()->create([
        'role' => 'Balsuojantysis',
        'name' => 'Original Voter',
        'email' => 'voter@example.com',
    ]);

    $updateData = [
        '_token' => csrf_token(),
        'name' => 'Updated Voter',
        'email' => 'updatedvoter@example.com',
        'pedagogical_name' => 'Updated Pedagogical',
        'role' => 'Balsuojantysis',
        'gender' => '1',
    ];

    $response = $this->actingAs($itAdmin)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $voter), $updateData);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'user_id' => $voter->user_id,
        'name' => 'Updated Voter',
        'email' => 'updatedvoter@example.com',
        'role' => 'Balsuojantysis',
    ]);

    Session::flush();
    Session::invalidate();
});

it('allows IT admin to update a secretary profile', function () {
    Session::start();

    $itAdmin = User::factory()->create(['role' => 'IT administratorius']);
    $secretary = User::factory()->create([
        'role' => 'Sekretorius',
        'name' => 'Original Secretary',
        'email' => 'secretary@example.com',
    ]);

    $updateData = [
        '_token' => csrf_token(),
        'name' => 'Updated Secretary',
        'email' => 'updatedsecretary@example.com',
        'pedagogical_name' => 'Updated Pedagogical',
        'role' => 'Sekretorius',
        'gender' => '0',
    ];

    $response = $this->actingAs($itAdmin)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $secretary), $updateData);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'user_id' => $secretary->user_id,
        'name' => 'Updated Secretary',
        'email' => 'updatedsecretary@example.com',
        'role' => 'Sekretorius',
    ]);

    Session::flush();
    Session::invalidate();
});

it('allows IT admin to update another IT admin profile', function () {
    Session::start();

    $itAdmin1 = User::factory()->create(['role' => 'IT administratorius']);
    $itAdmin2 = User::factory()->create([
        'role' => 'IT administratorius',
        'name' => 'Original IT Admin',
        'email' => 'itadmin@example.com',
    ]);

    $updateData = [
        '_token' => csrf_token(),
        'name' => 'Updated IT Admin',
        'email' => 'updateditadmin@example.com',
        'pedagogical_name' => 'Updated Pedagogical',
        'role' => 'IT administratorius',
        'gender' => '1',
    ];

    $response = $this->actingAs($itAdmin1)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('users.updateProfile', $itAdmin2), $updateData);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'user_id' => $itAdmin2->user_id,
        'name' => 'Updated IT Admin',
        'email' => 'updateditadmin@example.com',
        'role' => 'IT administratorius',
    ]);

    Session::flush();
    Session::invalidate();
});
