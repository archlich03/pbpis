<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use function Pest\Laravel\get;
use function Pest\Laravel\actingAs;
use Illuminate\Support\Facades\Session;

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
        'title' => 'Test Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'members' => $this->members->pluck('user_id')->toArray(),
        'is_ba_sp' => false,
    ]);

    Meeting::factory()->count(3)->create([
        'secretary_id' => $this->secretaryUser->user_id,
        'body_id' => $this->body->body_id,
    ]);
});

it('allows IT administrator to access the create meeting form', function () {
    actingAs($this->adminUser)
        ->get(route('meetings.create', $this->body))
        ->assertOk()
        ->assertViewIs('meetings.create')
        ->assertViewHas(['body', 'users']);
});

it('allows secretary to access the create meeting form', function () {
    actingAs($this->secretaryUser)
        ->get(route('meetings.create', $this->body))
        ->assertOk()
        ->assertViewIs('meetings.create')
        ->assertViewHas(['body', 'users']);
});

it('forbids voter from accessing the create meeting form', function () {
    actingAs($this->voterUser)
        ->get(route('meetings.create', $this->body))
        ->assertForbidden();
});

it('redirects guests to login when accessing the create meeting form', function () {
    get(route('meetings.create', $this->body))
        ->assertRedirect(route('login'));
});
