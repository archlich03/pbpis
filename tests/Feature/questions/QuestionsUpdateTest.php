<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\patch;
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

    // Create a question for update tests
    $this->question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Original Title',
        'decision' => '',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Original summary',
    ]);
});

it('allows secretary to update a question', function () {
    actingAs($this->secretaryUser);

    $response = patch(route('questions.update', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'title' => 'Updated Title',
        'decision' => 'some decision',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Updated summary',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'question_id' => $this->question->question_id,
        'title' => 'Updated Title',
        'decision' => 'Some decision.', // Capitalized + period appended by controller logic
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Updated summary',
    ]);
});

it('allows IT admin to update a question', function () {
    actingAs($this->adminUser);

    $response = patch(route('questions.update', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'title' => 'Admin Updated Title',
        'decision' => 'admin decision',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Admin updated summary',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'question_id' => $this->question->question_id,
        'title' => 'Admin Updated Title',
        'decision' => 'Admin decision.', // Capitalized + period appended
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Admin updated summary',
    ]);
});

it('forbids voter from updating a question', function () {
    actingAs($this->voterUser);

    patch(route('questions.update', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'title' => 'Voter Attempted Title',
        'decision' => 'voter decision',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Voter summary',
    ])->assertForbidden();

    // Assert no changes to DB
    $this->assertDatabaseHas('questions', [
        'question_id' => $this->question->question_id,
        'title' => 'Original Title',
        'decision' => '',
    ]);
});

it('forbids body member from updating a question', function () {
    actingAs($this->members->first());

    patch(route('questions.update', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'title' => 'Member Attempted Title',
        'decision' => 'member decision',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Member summary',
    ])->assertForbidden();

    $this->assertDatabaseHas('questions', [
        'question_id' => $this->question->question_id,
        'title' => 'Original Title',
        'decision' => '',
    ]);
});

it('redirects guest to login when trying to update a question', function () {
    patch(route('questions.update', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'title' => 'Guest Attempted Title',
        'decision' => 'guest decision',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Nebalsuoti',
        'summary' => 'Guest summary',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseHas('questions', [
        'question_id' => $this->question->question_id,
        'title' => 'Original Title',
        'decision' => '',
    ]);
});

it('fails validation if required fields are missing', function () {
    actingAs($this->secretaryUser);

    $this->from(route('questions.edit', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]))
        ->patch(route('questions.update', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
            '_token' => csrf_token(),
            // missing title, presenter_id, type
        ])
        ->assertRedirect(route('questions.edit', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]))
        ->assertSessionHasErrors(['title', 'presenter_id', 'type']);
});
