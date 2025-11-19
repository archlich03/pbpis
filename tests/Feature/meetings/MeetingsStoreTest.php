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
});

it('allows IT administrator to store a meeting', function () {
    actingAs($this->adminUser);

    $response = post(route('meetings.store', $this->body), [
        '_token' => csrf_token(),
        'secretary_id' => $this->secretaryUser->user_id,
        'is_evote' => 1,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->toDateString(),
        'vote_end' => now()->addDay()->toDateString(),
    ]);

    $response->assertRedirect(route('bodies.show', $this->body));

    expect(Meeting::count())->toBe(1);
});

it('allows secretary to store a meeting', function () {
    actingAs($this->secretaryUser);

    $response = post(route('meetings.store', $this->body), [
        '_token' => csrf_token(),
        'secretary_id' => $this->secretaryUser->user_id,
        'is_evote' => 0,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->toDateString(),
        'vote_end' => now()->addDays(2)->toDateString(),
    ]);

    $response->assertRedirect(route('bodies.show', $this->body));

    expect(Meeting::count())->toBe(1);
});

it('forbids voter from storing a meeting', function () {
    actingAs($this->members->first());

    post(route('meetings.store', $this->body), [
        '_token' => csrf_token(),
        'secretary_id' => $this->secretaryUser->user_id,
        'is_evote' => 0,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->toDateString(),
        'vote_end' => now()->addDays(2)->toDateString(),
    ])->assertForbidden();

    expect(Meeting::count())->toBe(0);
});

it('redirects guests to login when trying to store a meeting', function () {
    post(route('meetings.store', $this->body), [
        '_token' => csrf_token(),
        'secretary_id' => $this->secretaryUser->user_id,
        'is_evote' => 0,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->toDateString(),
        'vote_end' => now()->addDays(2)->toDateString(),
    ])->assertRedirect(route('login'));

    expect(Meeting::count())->toBe(0);
});

it('fails validation if vote_end is before vote_start', function () {
    actingAs($this->adminUser);

    $this->from(route('meetings.create', $this->body))
        ->post(route('meetings.store', $this->body), [
            '_token' => csrf_token(),
            'secretary_id' => $this->secretaryUser->user_id,
            'is_evote' => 1,
            'meeting_date' => now()->toDateString(),
            'vote_start' => now()->toDateString(),
            'vote_end' => now()->subDay()->toDateString(),
        ])
        ->assertRedirect(route('meetings.create', $this->body))
        ->assertSessionHasErrors('vote_end');

    expect(Meeting::count())->toBe(0);
});

it('stores meeting with "Planned" status', function () {
    actingAs($this->adminUser);

    post(route('meetings.store', $this->body), [
        '_token' => csrf_token(),
        'secretary_id' => $this->secretaryUser->user_id,
        'is_evote' => 1,
        'meeting_date' => now()->toDateString(),
        'vote_start' => null,
        'vote_end' => null,
    ]);

    $meeting = Meeting::first();
    expect($meeting)->not->toBeNull()
        ->and($meeting->status)->toBe('Suplanuotas');
});

it('allows IT administrator to be assigned as meeting secretary', function () {
    actingAs($this->secretaryUser);

    $response = post(route('meetings.store', $this->body), [
        '_token' => csrf_token(),
        'secretary_id' => $this->adminUser->user_id, // IT admin as secretary
        'is_evote' => 1,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->toDateString(),
        'vote_end' => now()->addDay()->toDateString(),
    ]);

    $response->assertRedirect(route('bodies.show', $this->body));

    $meeting = Meeting::first();
    expect($meeting)->not->toBeNull()
        ->and($meeting->secretary_id)->toBe($this->adminUser->user_id)
        ->and($meeting->secretary->role)->toBe('IT administratorius');
});