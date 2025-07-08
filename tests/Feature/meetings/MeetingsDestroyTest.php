<?php

use App\Models\Meeting;
use App\Models\User;
use App\Models\Body;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Support\Facades\Auth;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

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

it('allows IT admin to delete a meeting', function () {
    actingAs($this->adminUser);

    $response = delete(route('meetings.destroy', $this->meeting->meeting_id), [
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('bodies.show', $this->body));
    $response->assertSessionHas('success', 'Meeting and all related data deleted successfully.');

    expect(Meeting::find($this->meeting->meeting_id))->toBeNull();
});

it('allows secretary to delete a meeting', function () {
    actingAs($this->secretaryUser);

    $response = delete(route('meetings.destroy', $this->meeting->meeting_id), [
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('bodies.show', $this->body));
    $response->assertSessionHas('success', 'Meeting and all related data deleted successfully.');

    expect(Meeting::find($this->meeting->meeting_id))->toBeNull();
});

it('prevents non body voter to delete a meeting', function () {
    actingAs($this->voterUser);

    $response = delete(route('meetings.destroy', $this->meeting->meeting_id), [
        '_token' => csrf_token(),
    ]);

    $response->assertForbidden();

    expect(Meeting::find($this->meeting->meeting_id))->not->toBeNull();
});

it('prevents body voter to delete a meeting', function () {
    actingAs($this->members->first());

    $response = delete(route('meetings.destroy', $this->meeting->meeting_id), [
        '_token' => csrf_token(),
    ]);

    $response->assertForbidden();

    expect(Meeting::find($this->meeting->meeting_id))->not->toBeNull();
});

it('guest gets redirected to login when deleting a meeting', function () {
    $response = delete(route('meetings.destroy', $this->meeting->meeting_id), [
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('login'));

    expect(Meeting::find($this->meeting->meeting_id))->not->toBeNull();
});
