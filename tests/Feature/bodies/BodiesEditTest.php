<?php

use App\Models\Body;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use function Pest\Laravel\{actingAs, get, post, put, delete};

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Shared setup
beforeEach(function () {
    $this->adminUser = User::factory()->create(['role' => 'IT administratorius']);
    $this->secretaryUser = User::factory()->create(['role' => 'Sekretorius']);
    $this->voterUser = User::factory()->create(['role' => 'Balsuojantysis']);
});

it('allows IT admin to access bodies edit', function () {
    Session::start();
    app()->setLocale('en');
    
    $chairman = User::factory()->create(); // Ensure chairman exists
    $body = Body::factory()->create([
        'chairman_id' => $chairman->user_id,
    ]);
    actingAs($this->adminUser)
        ->get(route('bodies.edit', $body))
        ->assertSee(__('Edit body'));

    Session::flush();
    Session::invalidate();
});

it('allows secretaries to access bodies edit', function () {
    Session::start();
    app()->setLocale('en');

    $chairman = User::factory()->create(); // Ensure chairman exists
    $body = Body::factory()->create([
        'chairman_id' => $chairman->user_id,
    ]);
    actingAs($this->secretaryUser)
        ->get(route('bodies.edit', $body))
        ->assertSee(__('Edit body'));

    Session::flush();
    Session::invalidate();
});

it('prevents voters from accessing bodies edit', function () {
    Session::start();
    app()->setLocale('en');
    
    $chairman = User::factory()->create(); // Ensure chairman exists
    $body = Body::factory()->create([
        'chairman_id' => $chairman->user_id,
    ]);
    actingAs($this->voterUser)
        ->get(route('bodies.edit', $body))
        ->assertForbidden();

    Session::flush();
    Session::invalidate();
});

it('prevents guests from bodies edit', function () {
    $chairman = User::factory()->create(); // Ensure chairman exists
    $body = Body::factory()->create([
        'chairman_id' => $chairman->user_id,
    ]);
    $response = $this->get(route('bodies.edit', $body));

    $response->assertRedirect(route('login'));
});