<?php

use App\Models\Body;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Session::start();

    $this->adminUser = User::factory()->create(['role' => 'IT administratorius']);
    $this->secretaryUser = User::factory()->create(['role' => 'Sekretorius']);
    $this->voterUser = User::factory()->create(['role' => 'Balsuojantysis']);

    $this->chairman = User::factory()->create();
    $this->members = User::factory()->count(3)->create([
        'role' => 'Balsuojantysis',
    ]);
    $this->body = Body::factory()->create([
        'title' => 'Sample Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'members' => $this->members->pluck('user_id')->toArray(),
        'is_ba_sp' => 1,
    ]);
});

it('allows IT admin to view a body', function () {
    actingAs($this->adminUser)
        ->get(route('bodies.show', $this->body))
        ->assertOk()
        ->assertViewIs('bodies.show')
        ->assertViewHas('body', function ($viewBody) {
            return (string) $viewBody->body_id === (string) $this->body->body_id;
        });
});

it('allows secretary to view a body', function () {
    actingAs($this->secretaryUser)
        ->get(route('bodies.show', $this->body))
        ->assertOk()
        ->assertViewIs('bodies.show')
        ->assertViewHas('body', function ($viewBody) {
            return (string) $viewBody->body_id === (string) $this->body->body_id;
        });
});

it('allows voter to view a body', function () {
    actingAs($this->voterUser)
        ->get(route('bodies.show', $this->body))
        ->assertOk()
        ->assertViewIs('bodies.show')
        ->assertViewHas('body', function ($viewBody) {
            return (string) $viewBody->body_id === (string) $this->body->body_id;
        });
});

it('redirects guest to login when viewing a body', function () {
    get(route('bodies.show', $this->body))
        ->assertRedirect(route('login'));
});
