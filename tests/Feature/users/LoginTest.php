<?php
use App\Models\User;

beforeEach(function () {
    $this->withSession(['_token' => 'test_token']);
});

it('redirects guests from dashboard to login page', function() {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

it('prevents login with invalid email', function () {
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'fake@example.com',
        'password' => 'correct-password',
        '_token' => 'test_token',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors();
    $this->assertGuest();
});

it('prevents login with invalid password', function () {
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'real@example.com',
        'password' => 'wrong-password',
        '_token' => 'test_token',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors();
    $this->assertGuest();
});

it('allows user to log in with correct credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'user@example.com',
        'password' => 'password123',
        '_token' => 'test_token',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('logs the user out', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post(route('logout'), [
        '_token' => 'test_token',
    ]);

    $response->assertRedirect('/');
    $this->assertGuest();
});
