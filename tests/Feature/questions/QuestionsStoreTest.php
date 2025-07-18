<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\post;
use function Pest\Laravel\actingAs;

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

    $this->meeting = Meeting::factory()->create([
        'body_id' => $this->body->body_id,
        'secretary_id' => $this->secretaryUser->user_id,
        'is_evote' => 0,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->toDateTimeString(),
        'vote_end' => now()->addDay()->toDateTimeString(),
        'status' => 'Suplanuotas',
    ]);
});

it ('allows secretary to store new questions', function () {
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => 'Test summary',
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test question',
        'decision' => '',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Test summary',
    ]);
});

it ('allows IT admin to store new questions', function () {
    actingAs($this->adminUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => 'Test summary',
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test question',
        'decision' => '',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Test summary',
    ]);
});

it ('prevents non body member to store new questions', function () {
    actingAs($this->voterUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => 'Test summary',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test question',
        'decision' => '',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Test summary',
    ]);
});

it ('prevents body member to store new questions', function () {
    actingAs($this->members->first())
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => 'Test summary',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test question',
        'decision' => '',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Test summary',
    ]);
});

it ('prevents guest to store new questions', function () {
        post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => 'Test summary',
        ])
        ->assertRedirect(route('login'));

    $this->assertDatabaseMissing('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test question',
        'decision' => '',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Test summary',
    ]);
});