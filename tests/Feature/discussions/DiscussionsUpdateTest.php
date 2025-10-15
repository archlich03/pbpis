<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Discussion;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\patch;
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
        'is_evote' => 1,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->subHour()->toDateTimeString(),
        'vote_end' => now()->addHour()->toDateTimeString(),
        'status' => 'Vyksta',
    ]);

    $this->question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test Question',
        'decision' => 'Test Decision',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Balsuoti dauguma',
        'summary' => 'Test summary',
    ]);

    $this->discussion = Discussion::create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
        'content' => 'Original content',
    ]);
});

it('allows user to edit their own comment', function () {
    actingAs($this->members->first());

    $response = patch(route('discussions.update', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
        'content' => 'Updated content',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('discussions', [
        'discussion_id' => $this->discussion->discussion_id,
        'content' => 'Updated content',
    ]);
});

it('prevents user from editing others comments', function () {
    actingAs($this->members->last()); // Different user

    $response = patch(route('discussions.update', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
        'content' => 'Unauthorized edit',
    ]);

    $response->assertForbidden();
});

it('prevents editing when meeting is not in progress', function () {
    $this->meeting->update(['status' => 'Baigtas']);

    actingAs($this->members->first());

    $response = patch(route('discussions.update', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
        'content' => 'Should not work',
    ]);

    $response->assertForbidden();
});

it('validates content is required when updating', function () {
    actingAs($this->members->first());

    $response = patch(route('discussions.update', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
        'content' => '',
    ]);

    $response->assertSessionHasErrors('content');
});

it('preserves active question tab in session after update', function () {
    actingAs($this->members->first());

    patch(route('discussions.update', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
        'content' => 'Updated content',
    ]);

    expect(session('active_question_id'))->toBe($this->question->question_id);
});
