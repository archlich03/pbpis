<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\delete;
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

    // Create a question for delete tests
    $this->question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Question to Delete',
        'decision' => '',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Summary',
    ]);
});

it('allows secretary to delete a question', function () {
    actingAs($this->secretaryUser);

    $response = delete(route('questions.destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseMissing('questions', [
        'question_id' => $this->question->question_id,
    ]);
});

it('allows IT admin to delete a question', function () {
    actingAs($this->adminUser);

    $response = delete(route('questions.destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseMissing('questions', [
        'question_id' => $this->question->question_id,
    ]);
});

it('forbids voter from deleting a question', function () {
    actingAs($this->voterUser);

    delete(route('questions.destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
    ])->assertForbidden();

    $this->assertDatabaseHas('questions', [
        'question_id' => $this->question->question_id,
    ]);
});

it('forbids body member from deleting a question', function () {
    actingAs($this->members->first());

    delete(route('questions.destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
    ])->assertForbidden();

    $this->assertDatabaseHas('questions', [
        'question_id' => $this->question->question_id,
    ]);
});

it('redirects guest to login when trying to delete a question', function () {
    delete(route('questions.destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
    ])->assertRedirect(route('login'));

    $this->assertDatabaseHas('questions', [
        'question_id' => $this->question->question_id,
    ]);
});

it('returns 402 if meeting and question do not match', function () {
    actingAs($this->adminUser);

    // Create another meeting unrelated to question, assign valid secretary_id
    $otherMeeting = Meeting::factory()->create([
        'secretary_id' => $this->secretaryUser->user_id, // assign valid user ID here
    ]);

    delete(route('questions.destroy', ['meeting' => $otherMeeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
    ])->assertStatus(402);

    $this->assertDatabaseHas('questions', [
        'question_id' => $this->question->question_id,
    ]);
});
