<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
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
        'title' => 'Test Body',
        'classification' => 'SPK',
        'chairman_id' => $this->chairman->user_id,
        'members' => $this->members->pluck('user_id')->toArray(),
        'is_ba_sp' => false,
    ]);

    actingAs($this->secretaryUser);
});

it('shows meeting with status Suplanuotas before voting starts', function () {
    $meeting = Meeting::factory()->create([
        'status' => 'Suplanuotas',
        'vote_start' => now()->addDay(),
        'vote_end' => now()->addDays(2),
        'secretary_id' => $this->secretaryUser->user_id,
        'body_id' => $this->body->body_id,
    ]);

    $response = get(route('meetings.show', $meeting->meeting_id));

    $response->assertOk()
        ->assertViewIs('meetings.show')
        ->assertViewHas('meeting')
        ->assertViewHas('users');

    // Status is no longer updated on page load, it's updated by cron
    $meeting->refresh();
    expect($meeting->status)->toBe('Suplanuotas');
});

it('shows meeting with status Vyksta during voting period', function () {
    $meeting = Meeting::factory()->create([
        'status' => 'Vyksta',
        'vote_start' => now()->subHour(),
        'vote_end' => now()->addHour(),
        'secretary_id' => $this->secretaryUser->user_id,
        'body_id' => $this->body->body_id,
    ]);

    $response = get(route('meetings.show', $meeting->meeting_id));

    $response->assertOk()
        ->assertViewIs('meetings.show')
        ->assertViewHas('meeting')
        ->assertViewHas('users');

    // Status is no longer updated on page load, it's updated by cron
    $meeting->refresh();
    expect($meeting->status)->toBe('Vyksta');
});

it('shows meeting with status Baigtas after voting ended', function () {
    $meeting = Meeting::factory()->create([
        'status' => 'Baigtas',
        'vote_start' => now()->subDays(3),
        'vote_end' => now()->subDay(),
        'secretary_id' => $this->secretaryUser->user_id,
        'body_id' => $this->body->body_id,
    ]);

    $response = get(route('meetings.show', $meeting->meeting_id));

    $response->assertOk()
        ->assertViewIs('meetings.show')
        ->assertViewHas('meeting')
        ->assertViewHas('users');

    // Status is no longer updated on page load, it's updated by cron
    $meeting->refresh();
    expect($meeting->status)->toBe('Baigtas');
});

it('returns 404 if meeting not found', function () {
    get(route('meetings.show', 'non-existent-id'))->assertNotFound();
});

it('returns 403 if user is not part of body', function () {
    actingAs($this->voterUser);
    $meeting = Meeting::factory()->create([
        'status' => 'Vyksta',  // test update to Baigtas
        'vote_start' => now()->subDays(3),
        'vote_end' => now()->subDay(),
        'secretary_id' => $this->secretaryUser->user_id,
        'body_id' => $this->body->body_id,
    ]);

    get(route('meetings.show', $meeting->meeting_id))
        ->assertForbidden();
});