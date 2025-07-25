<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\put;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    Session::start();

    $this->adminUser = User::factory()->create(['role' => 'IT administratorius']);
    $this->secretaryUser = User::factory()->create(['role' => 'Sekretorius']);
    $this->voterUser = User::factory()->create(['role' => 'Balsuojantysis']);

    $this->chairman = User::factory()->create();
    $this->members = User::factory()->count(3)->create(['role' => 'Balsuojantysis']);

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
        'vote_start' => now()->subHour()->toDateTimeString(),
        'vote_end' => now()->addHour()->toDateTimeString(),
        'status' => 'Vyksta',
    ]);

    // Create attendances to ensure quorum
    $this->meeting->attendances()->create(['user_id' => $this->chairman->user_id]);
    foreach ($this->members as $member) {
        $this->meeting->attendances()->create(['user_id' => $member->user_id]);
    }

    $this->question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test Question',
        'decision' => '',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Dalyvių dauguma',
        'summary' => 'Test summary',
    ]);
});

it('allows a body member to vote', function () {
    actingAs($this->members->first());

    $response = put(route('votes.store', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'choice' => 'Už',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
        'choice' => 'Už',
    ]);
});

it('updates existing vote if already voted', function () {
    actingAs($this->members->first());

    // Create existing vote
    Vote::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
        'choice' => 'Prieš',
    ]);

    put(route('votes.store', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'choice' => 'Už',
    ])->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
        'choice' => 'Už',
    ]);
});

it('forbids voting for users who are not body members', function () {
    actingAs($this->voterUser); // Not a member of $this->body

    put(route('votes.store', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'choice' => 'Už',
    ])->assertForbidden();

    $this->assertDatabaseMissing('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->voterUser->user_id,
    ]);
});

it('fails validation if choice is missing', function () {
    actingAs($this->members->first());

    $this->from(route('meetings.show', $this->meeting))
        ->put(route('votes.store', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
            '_token' => csrf_token(),
            // choice missing
        ])
        ->assertSessionHasErrors(['choice']);
});

it('does not save vote if current time is outside voting period', function () {
    actingAs($this->members->first());

    // Make vote period expired
    $this->meeting->vote_start = now()->subDays(2);
    $this->meeting->vote_end = now()->subDay();
    $this->meeting->save();

    put(route('votes.store', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'choice' => 'Už',
    ])->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseMissing('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
    ]);
});

it('redirects guest to login when trying to vote', function () {
    put(route('votes.store', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'choice' => 'Už',
    ])->assertRedirect(route('login'));
});
