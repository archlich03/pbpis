<?php

use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Body;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Session::start();

    $this->adminUser = User::factory()->create(['role' => 'IT administratorius']);
    $this->secretaryUser = User::factory()->create(['role' => 'Sekretorius']);
    $this->voterUser = User::factory()->create(['role' => 'Balsuojantysis']);

    $this->chairman = User::factory()->create();
    $this->members = User::factory()->count(3)->create([
        'role' => 'Balsuojantysis',
    ]);

    $this->payload = [
        '_token' => csrf_token(),
        'title' => 'Sample Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'members' => $this->members->pluck('user_id')->toArray(),
        'is_ba_sp' => 1,
    ];
});

it('allows IT admins to create a body', function () {
    actingAs($this->adminUser)
        ->post(route('bodies.store'), $this->payload)
        ->assertRedirect(route('bodies.index'));

    assertDatabaseHas('bodies', [
        'title' => 'Sample Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'is_ba_sp' => 1,
    ]);
});

it('forbids secretary from creating a body', function () {
    actingAs($this->secretaryUser)
        ->post(route('bodies.store'), $this->payload)
        ->assertForbidden();
    
    assertDatabaseMissing('bodies', [
        'title' => 'Sample Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'is_ba_sp' => 1,
    ]);
});

it('forbids voter from creating a body', function () {
    actingAs($this->members->first())
        ->post(route('bodies.store'), $this->payload)
        ->assertForbidden();

    assertDatabaseMissing('bodies', [
        'title' => 'Sample Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'is_ba_sp' => 1,
    ]);
});

it('redirects guest when trying to create a body', function () {
    post(route('bodies.store'), $this->payload)
        ->assertRedirect(route('login'));

    assertDatabaseMissing('bodies', [
        'title' => 'Sample Body',
        'chairman_id' => $this->chairman->user_id,
    ]);
});