<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\delete;
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
        'status' => 'Suplanuotas',
    ]);

    $this->question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test Question',
        'decision' => '',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Test summary',
    ]);

    $this->vote = Vote::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
        'choice' => 'yes',
    ]);
});

it('allows a body member to delete their vote', function () {
    actingAs($this->members->first());

    $response = delete(route('votes.destroy', [
        '_token' => csrf_token(),
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
    ]));

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseMissing('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
    ]);
});

it('forbids users who are not body members from deleting a vote', function () {
    actingAs($this->voterUser);

    delete(route('votes.destroy', [
        '_token' => csrf_token(),
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
    ]))->assertForbidden();

    $this->assertDatabaseHas('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
    ]);
});

it('redirects guest to login when trying to delete a vote', function () {
    delete(route('votes.destroy', [
        '_token' => csrf_token(),
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
    ]))->assertRedirect(route('login'));

    $this->assertDatabaseHas('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
    ]);
});
