<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\post;
use function Pest\Laravel\delete;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    Session::start();

    $this->adminUser = User::factory()->create(['role' => 'IT administratorius']);
    $this->secretaryUser = User::factory()->create(['role' => 'Sekretorius']);
    $this->voterUser = User::factory()->create(['role' => 'Balsuojantysis']);

    $this->chairman = User::factory()->create();
    $this->members = User::factory()->count(8)->create(['role' => 'Balsuojantysis']);

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
        'type' => 'Dalyvių dauguma',
        'presenter_id' => $this->chairman->user_id,
    ]);
});

it('allows secretary to cast proxy vote for body member', function () {
    actingAs($this->secretaryUser);
    
    $targetMember = $this->members->first();

    $response = $this->put(route('votes.proxy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'user_id' => $targetMember->user_id,
        'choice' => 'Už',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    $this->assertDatabaseHas('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
        'choice' => 'Už',
    ]);
});

it('allows IT admin to cast proxy vote for body member', function () {
    actingAs($this->adminUser);
    
    $targetMember = $this->members->first();

    $response = $this->put(route('votes.proxy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'user_id' => $targetMember->user_id,
        'choice' => 'Prieš',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    $this->assertDatabaseHas('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
        'choice' => 'Prieš',
    ]);
});

it('forbids regular voters from casting proxy votes', function () {
    actingAs($this->voterUser);
    
    $targetMember = $this->members->first();

    $response = $this->put(route('votes.proxy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'user_id' => $targetMember->user_id,
        'choice' => 'Už',
    ]);

    $response->assertForbidden();
});

it('validates proxy vote choice', function () {
    actingAs($this->secretaryUser);
    
    $targetMember = $this->members->first();

    $this->from(route('meetings.show', $this->meeting))
        ->put(route('votes.proxy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
            '_token' => csrf_token(),
            'user_id' => $targetMember->user_id,
            'choice' => 'invalid_choice',
        ])
        ->assertSessionHasErrors(['choice']);
});

it('validates proxy vote user_id', function () {
    actingAs($this->secretaryUser);

    $this->from(route('meetings.show', $this->meeting))
        ->put(route('votes.proxy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
            '_token' => csrf_token(),
            'user_id' => 99999, // Non-existent user
            'choice' => 'Už',
        ])
        ->assertSessionHasErrors(['user_id']);
});

it('replaces existing proxy vote when casting new one', function () {
    actingAs($this->secretaryUser);
    
    $targetMember = $this->members->first();
    
    // Create existing vote
    Vote::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
        'choice' => 'Už',
    ]);

    $response = $this->put(route('votes.proxy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'user_id' => $targetMember->user_id,
        'choice' => 'Prieš',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    // Should only have one vote with the new choice
    $this->assertDatabaseHas('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
        'choice' => 'Prieš',
    ]);
    
    $this->assertDatabaseMissing('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
        'choice' => 'Už',
    ]);
});

it('auto-marks target user as attending when proxy vote is cast', function () {
    actingAs($this->secretaryUser);
    
    $targetMember = $this->members->first();
    
    // Remove user from attendance
    $this->meeting->attendances()->where('user_id', $targetMember->user_id)->delete();
    
    $this->assertDatabaseMissing('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $targetMember->user_id,
    ]);

    $response = $this->put(route('votes.proxy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'user_id' => $targetMember->user_id,
        'choice' => 'Už',
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    // Check that target user is now marked as attending
    $this->assertDatabaseHas('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $targetMember->user_id,
    ]);
});

it('allows secretary to delete proxy vote', function () {
    actingAs($this->secretaryUser);
    
    $targetMember = $this->members->first();
    
    // Create existing vote
    Vote::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
        'choice' => 'Už',
    ]);

    $response = $this->delete(route('votes.proxy-destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'user_id' => $targetMember->user_id,
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    $this->assertDatabaseMissing('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
    ]);
});

it('allows IT admin to delete proxy vote', function () {
    actingAs($this->adminUser);
    
    $targetMember = $this->members->first();
    
    // Create existing vote
    Vote::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
        'choice' => 'Prieš',
    ]);

    $response = $this->delete(route('votes.proxy-destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'user_id' => $targetMember->user_id,
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    $this->assertDatabaseMissing('votes', [
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
    ]);
});

it('forbids regular voters from deleting proxy votes', function () {
    actingAs($this->voterUser);
    
    $targetMember = $this->members->first();

    $response = $this->delete(route('votes.proxy-destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'user_id' => $targetMember->user_id,
    ]);

    $response->assertForbidden();
});

it('auto-marks target user as absent when their last proxy vote is deleted', function () {
    actingAs($this->secretaryUser);
    
    $targetMember = $this->members->first();
    
    // Create existing vote (only vote for this user)
    Vote::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $targetMember->user_id,
        'choice' => 'Už',
    ]);

    $response = $this->delete(route('votes.proxy-destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $this->question->question_id]), [
        '_token' => csrf_token(),
        'user_id' => $targetMember->user_id,
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    // Check that target user is now marked as absent
    $this->assertDatabaseMissing('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $targetMember->user_id,
    ]);
});
