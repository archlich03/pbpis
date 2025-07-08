<?php

use App\Models\Meeting;
use App\Models\User;
use App\Models\Body;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

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


it('allows IT administrator to update a meeting', function () {
    actingAs($this->adminUser);

    $newSecretary = User::factory()->create(['role' => 'Sekretorius']);

    $response = patch(route('meetings.update', $this->meeting), [
        '_token' => csrf_token(),
        'secretary_id' => $newSecretary->user_id,
        'is_evote' => 1,
        'meeting_date' => now()->addDays(2)->toDateString(),
        'vote_start' => now()->toDateTimeString(),
        'vote_end' => now()->addDay()->toDateTimeString(),
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->meeting->refresh();

    expect($this->meeting->secretary_id)->toBe($newSecretary->user_id)
        ->and($this->meeting->is_evote)->toBe(1);
});

it('forbids voter from updating a meeting', function () {
    actingAs($this->voterUser);

    patch(route('meetings.update', $this->meeting), [
        '_token' => csrf_token(),
        'secretary_id' => $this->voterUser->user_id,
        'is_evote' => 0,
        'meeting_date' => now()->toDateString(),
    ])->assertForbidden();
});

it('redirects guest to login when trying to update a meeting', function () {
    patch(route('meetings.update', $this->meeting), [
        '_token' => csrf_token(),
        'secretary_id' => $this->secretaryUser->user_id,
        'is_evote' => 1,
        'meeting_date' => now()->toDateString(),
    ])->assertRedirect(route('login'));
});

it('fails validation if required fields are missing', function () {
    actingAs($this->secretaryUser);

    $this->from(route('meetings.edit', $this->meeting))
        ->patch(route('meetings.update', $this->meeting), [
            '_token' => csrf_token(),
        ])
        ->assertRedirect(route('meetings.edit', $this->meeting))
        ->assertSessionHasErrors(['secretary_id', 'is_evote', 'meeting_date']);
});

it('fails validation if vote_end is before vote_start', function () {
    actingAs($this->adminUser);

    $this->from(route('meetings.edit', $this->meeting))
        ->patch(route('meetings.update', $this->meeting), [
            '_token' => csrf_token(),
            'secretary_id' => $this->adminUser->user_id,
            'is_evote' => 1,
            'meeting_date' => now()->toDateString(),
            'vote_start' => now()->toDateTimeString(),
            'vote_end' => now()->subDay()->toDateTimeString(),
        ])
        ->assertRedirect(route('meetings.edit', $this->meeting))
        ->assertSessionHasErrors('vote_end');
});

it('sets meeting status to "Planned" when outside voting period', function () {
    actingAs($this->adminUser);

    patch(route('meetings.update', $this->meeting), [
        '_token' => csrf_token(),
        'secretary_id' => $this->adminUser->user_id,
        'is_evote' => 1,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->addDay()->toDateTimeString(),
        'vote_end' => now()->addDays(2)->toDateTimeString(),
    ]);

    $this->meeting->refresh();
    expect($this->meeting->status)->toBe('Suplanuotas');
});

it('sets meeting status to "Started" during active vote window', function () {
    actingAs($this->adminUser);

    patch(route('meetings.update', $this->meeting), [
        '_token' => csrf_token(),
        'secretary_id' => $this->adminUser->user_id,
        'is_evote' => 1,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->subHour()->toDateTimeString(),
        'vote_end' => now()->addHour()->toDateTimeString(),
    ]);

    $this->meeting->refresh();
    expect($this->meeting->status)->toBe('Vyksta');
});

it('sets meeting status to "Finished" when voting period is over', function () {
    actingAs($this->adminUser);

    patch(route('meetings.update', $this->meeting), [
        '_token' => csrf_token(),
        'secretary_id' => $this->adminUser->user_id,
        'is_evote' => 1,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->subDays(2)->toDateTimeString(),
        'vote_end' => now()->subDay()->toDateTimeString(),
    ]);

    $this->meeting->refresh();
    expect($this->meeting->status)->toBe('Baigtas');
});
