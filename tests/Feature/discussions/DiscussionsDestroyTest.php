<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Discussion;
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
        'content' => 'Test content',
    ]);
});

it('allows user to delete their own comment', function () {
    actingAs($this->members->first());

    $response = delete(route('discussions.destroy', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseMissing('discussions', [
        'discussion_id' => $this->discussion->discussion_id,
    ]);
});

it('allows secretary to delete any comment', function () {
    actingAs($this->secretaryUser);

    $response = delete(route('discussions.destroy', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseMissing('discussions', [
        'discussion_id' => $this->discussion->discussion_id,
    ]);
});

it('allows IT admin to delete any comment', function () {
    actingAs($this->adminUser);

    $response = delete(route('discussions.destroy', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseMissing('discussions', [
        'discussion_id' => $this->discussion->discussion_id,
    ]);
});

it('prevents regular user from deleting others comments', function () {
    actingAs($this->members->last()); // Different user

    $response = delete(route('discussions.destroy', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
    ]);

    $response->assertForbidden();
});

it('deletes replies when parent is deleted', function () {
    actingAs($this->members->first());

    // Create reply
    $reply = Discussion::create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->last()->user_id,
        'parent_id' => $this->discussion->discussion_id,
        'content' => 'Reply content',
    ]);

    // Delete parent
    delete(route('discussions.destroy', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
    ]);

    // Both parent and reply should be deleted
    $this->assertDatabaseMissing('discussions', [
        'discussion_id' => $this->discussion->discussion_id,
    ]);

    $this->assertDatabaseMissing('discussions', [
        'discussion_id' => $reply->discussion_id,
    ]);
});

it('prevents deleting when meeting is not in progress', function () {
    $this->meeting->update(['status' => 'Baigtas']);

    actingAs($this->members->first());

    $response = delete(route('discussions.destroy', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
    ]);

    $response->assertForbidden();
});

it('preserves active question tab in session after delete', function () {
    actingAs($this->members->first());

    delete(route('discussions.destroy', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id,
        'discussion' => $this->discussion->discussion_id,
    ]), [
        '_token' => csrf_token(),
    ]);

    expect(session('active_question_id'))->toBe($this->question->question_id);
});
