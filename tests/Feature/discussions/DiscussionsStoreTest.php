<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Discussion;
use App\Models\MeetingAttendance;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\post;
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
        'is_evote' => 1, // E-vote meeting
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
});

it('allows a body member to post a discussion comment', function () {
    actingAs($this->members->first());

    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'This is a test comment',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('discussions', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
        'content' => 'This is a test comment',
        'parent_id' => null,
    ]);
});

it('auto-marks body member as attending when posting comment', function () {
    actingAs($this->members->first());

    post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'This is a test comment',
    ]);

    $this->assertDatabaseHas('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $this->members->first()->user_id,
        'status' => 'Dalyvauja',
    ]);
});

it('allows secretary to post a discussion comment', function () {
    actingAs($this->secretaryUser);

    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'Secretary comment',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('discussions', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->secretaryUser->user_id,
        'content' => 'Secretary comment',
    ]);
});

it('allows IT admin to post a discussion comment', function () {
    actingAs($this->adminUser);

    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'Admin comment',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('discussions', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->adminUser->user_id,
        'content' => 'Admin comment',
    ]);
});

it('prevents non-body members from posting comments', function () {
    actingAs($this->voterUser); // Not a member of this body

    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'Unauthorized comment',
    ]);

    $response->assertForbidden();
});

it('prevents posting comments on non-evote meetings', function () {
    $this->meeting->update(['is_evote' => 0]);

    actingAs($this->members->first());

    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'Should not work',
    ]);

    $response->assertForbidden();
});

it('prevents posting comments when meeting is not in progress', function () {
    $this->meeting->update(['status' => 'Suplanuotas']);

    actingAs($this->members->first());

    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'Should not work',
    ]);

    $response->assertForbidden();
});

it('allows replying to existing comments', function () {
    actingAs($this->members->first());

    // Create parent comment
    $parentComment = Discussion::create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
        'content' => 'Parent comment',
        'created_at' => now()->subMinutes(2),
        'updated_at' => now()->subMinutes(2),
    ]);

    // Travel forward in time to avoid rate limit
    $this->travel(61)->seconds();

    // Reply to parent
    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'Reply comment',
        'parent_id' => $parentComment->discussion_id,
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('discussions', [
        'question_id' => $this->question->question_id,
        'user_id' => $this->members->first()->user_id,
        'content' => 'Reply comment',
        'parent_id' => $parentComment->discussion_id,
    ]);
});

it('enforces rate limiting (1 comment per minute)', function () {
    actingAs($this->members->first());

    // First comment
    post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'First comment',
    ]);

    // Second comment immediately after
    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'Second comment too soon',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('validates content is required', function () {
    actingAs($this->members->first());

    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => '',
    ]);

    $response->assertSessionHasErrors('content');
});

it('validates content max length is 5000 characters', function () {
    actingAs($this->members->first());

    $response = post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => str_repeat('a', 5001),
    ]);

    $response->assertSessionHasErrors('content');
});

it('preserves active question tab in session', function () {
    actingAs($this->members->first());

    post(route('discussions.store', [
        'meeting' => $this->meeting->meeting_id,
        'question' => $this->question->question_id
    ]), [
        '_token' => csrf_token(),
        'content' => 'Test comment',
    ]);

    expect(session('active_question_id'))->toBe($this->question->question_id);
});
