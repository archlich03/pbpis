<?php

use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Body;
use function Pest\Laravel\delete;
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

    $this->body = Body::factory()->create([
        'title' => 'Original Title',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'members' => $this->members->pluck('user_id')->toArray(),
        'is_ba_sp' => 0,
    ]);
});

it('allows IT admin to delete a body', function () {
    actingAs($this->adminUser)
        ->delete(route('bodies.destroy', $this->body), [
            '_token' => csrf_token(),
        ])
        ->assertRedirect(route('bodies.index'));

    assertDatabaseMissing('bodies', [
        'body_id' => $this->body->body_id,
    ]);
});

it('forbids secretary from deleting a body', function () {
    actingAs($this->secretaryUser)
        ->delete(route('bodies.destroy', $this->body), [
            '_token' => csrf_token(),
        ])
        ->assertForbidden();

    assertDatabaseHas('bodies', [
        'body_id' => $this->body->body_id,
    ]);
});

it('forbids voter from deleting a body', function () {
    actingAs($this->voterUser)
        ->delete(route('bodies.destroy', $this->body), [
            '_token' => csrf_token(),
        ])
        ->assertForbidden();

    assertDatabaseHas('bodies', [
        'body_id' => $this->body->body_id,
    ]);
});

it('redirects guests when trying to delete a body', function () {
    post(route('bodies.destroy', $this->body), [
        '_method' => 'DELETE',
        '_token' => csrf_token(),
    ])->assertRedirect(route('login'));

    assertDatabaseHas('bodies', [
        'body_id' => $this->body->body_id,
    ]);
});