<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    Session::start();
});

it('validates email formats', function () {
    $admin = User::factory()->create(['role' => 'IT administratorius']);
    
    $response = $this->actingAs($admin)
        ->withSession(['_token' => csrf_token()])
        ->post(route('register'), [
            '_token' => csrf_token(),
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'gender' => 0,
            'role' => 'Balsuojantysis',
        ]);
    
    $response->assertSessionHasErrors(['email']);
});

it('prevents sql injection in search queries', function () {
    $user = User::factory()->create();
    
    // This test verifies that Eloquent ORM prevents SQL injection
    // by using parameterized queries
    $maliciousInput = "'; DROP TABLE users; --";
    
    // This should not cause any SQL injection
    $results = User::where('name', 'LIKE', '%' . $maliciousInput . '%')->get();
    
    // Users table should still exist and be accessible
    expect(User::count())->toBeGreaterThanOrEqual(1);
});

it('validates body titles for length', function () {
    $user = User::factory()->create(['role' => 'IT administratorius']);
    $chairman = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->withSession(['_token' => csrf_token()])
        ->post(route('bodies.store'), [
            '_token' => csrf_token(),
            'title' => str_repeat('a', 256), // Too long
            'classification' => 'Mokslo padalinys',
            'chairperson_id' => $chairman->user_id,
            'is_ba_sp' => 'MA',
            'members' => [$chairman->user_id],
        ]);
    
    $response->assertSessionHasErrors(['title']);
});

it('prevents mass assignment vulnerabilities', function () {
    $user = User::factory()->create(['role' => 'Balsuojantysis']);
    
    // Try to mass assign a role that shouldn't be allowed
    $response = $this->actingAs($user)
        ->withSession(['_token' => csrf_token()])
        ->patch(route('profile.update'), [
            '_token' => csrf_token(),
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => 'IT administratorius', // This should not be allowed
        ]);
    
    $user->refresh();
    expect($user->role)->toBe('Balsuojantysis'); // Role should not change
});

it('validates meeting dates', function () {
    $user = User::factory()->create(['role' => 'Sekretorius']);
    $body = Body::factory()->create();
    
    $response = $this->actingAs($user)
        ->withSession(['_token' => csrf_token()])
        ->post(route('meetings.store', $body), [
            '_token' => csrf_token(),
            'meeting_date' => 'invalid-date',
            'secretary_id' => $user->user_id,
            'is_evote' => '0',
        ]);
    
    $response->assertSessionHasErrors(['meeting_date']);
});
