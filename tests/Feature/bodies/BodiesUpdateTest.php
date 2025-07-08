<?php

use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Body;
use function Pest\Laravel\patch;
use function Pest\Laravel\actingAs;
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

    $this->body = Body::factory()->create([
        'title' => 'Original Title',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'members' => $this->members->pluck('user_id')->toArray(),
        'is_ba_sp' => 0,
    ]);

    $this->updatePayload = [
        '_token' => csrf_token(),
        'title' => 'Updated Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'members' => $this->members->pluck('user_id')->toArray(),
        'is_ba_sp' => 1,
    ];
});

it('allows IT admin to update a body', function () {
    actingAs($this->adminUser)
        ->patch(route('bodies.update', $this->body), $this->updatePayload)
        ->assertRedirect(route('bodies.index'));

    assertDatabaseHas('bodies', [
        'body_id' => $this->body->body_id,
        'title' => 'Updated Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'is_ba_sp' => 1,
    ]);
});

it('allows secretary to update a body', function () {
    actingAs($this->secretaryUser)
        ->patch(route('bodies.update', $this->body), $this->updatePayload)
        ->assertRedirect(route('bodies.index'));

    assertDatabaseHas('bodies', [
        'body_id' => $this->body->body_id,
        'title' => 'Updated Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'is_ba_sp' => 1,
    ]);
});

it('forbids voter from updating a body', function () {
    actingAs($this->voterUser)
        ->patch(route('bodies.update', $this->body), $this->updatePayload)
        ->assertForbidden();

    assertDatabaseMissing('bodies', [
        'body_id' => $this->body->body_id,
        'title' => 'Updated Body',
    ]);
});

it('redirects guests when trying to update a body', function () {
    patch(route('bodies.update', $this->body), $this->updatePayload)
        ->assertRedirect(route('login'));

    assertDatabaseMissing('bodies', [
        'body_id' => $this->body->body_id,
        'title' => 'Updated Body',
    ]);
});
