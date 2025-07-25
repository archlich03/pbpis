<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\post;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    Session::start();

    $this->adminUser = User::factory()->create(['role' => 'IT administratorius']);
    $this->secretaryUser = User::factory()->create(['role' => 'Sekretorius']);
    $this->voterUser = User::factory()->create(['role' => 'Balsuojantysis']);

    $this->chairman = User::factory()->create();
    $this->members = User::factory()->count(10)->create(['role' => 'Balsuojantysis']);

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
});

it('allows secretary to toggle member attendance', function () {
    actingAs($this->secretaryUser);
    
    $member = $this->members->first();

    $response = $this->from(route('meetings.show', $this->meeting))
        ->post(route('attendance.toggle', ['meeting' => $this->meeting->meeting_id]), [
            '_token' => csrf_token(),
            'user_id' => $member->user_id,
        ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    $this->assertDatabaseHas('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $member->user_id,
    ]);
});

it('allows IT admin to toggle member attendance', function () {
    actingAs($this->adminUser);
    
    $member = $this->members->first();

    $response = $this->from(route('meetings.show', $this->meeting))
        ->post(route('attendance.toggle', ['meeting' => $this->meeting->meeting_id]), [
            '_token' => csrf_token(),
            'user_id' => $member->user_id,
        ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    $this->assertDatabaseHas('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $member->user_id,
    ]);
});

it('forbids regular voters from toggling attendance', function () {
    actingAs($this->voterUser);
    
    $member = $this->members->first();

    $response = post(route('attendance.toggle', ['meeting' => $this->meeting->meeting_id]), [
        '_token' => csrf_token(),
        'user_id' => $member->user_id,
    ]);

    $response->assertForbidden();
});

it('toggles attendance off when member is already attending', function () {
    actingAs($this->secretaryUser);
    
    $member = $this->members->first();
    
    // First mark as attending
    $this->meeting->attendances()->create(['user_id' => $member->user_id]);
    
    $response = $this->from(route('meetings.show', $this->meeting))
        ->post(route('attendance.toggle', ['meeting' => $this->meeting->meeting_id]), [
            '_token' => csrf_token(),
            'user_id' => $member->user_id,
        ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    $this->assertDatabaseMissing('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $member->user_id,
    ]);
});

it('allows secretary to mark all members present', function () {
    actingAs($this->secretaryUser);

    $response = $this->from(route('meetings.show', $this->meeting))
        ->post(route('attendance.mark-all', ['meeting' => $this->meeting->meeting_id]), [
            '_token' => csrf_token(),
        ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    // Check that all body members are marked as attending
    foreach ($this->body->members as $member) {
        $this->assertDatabaseHas('meeting_attendances', [
            'meeting_id' => $this->meeting->meeting_id,
            'user_id' => $member->user_id,
        ]);
    }
});

it('allows secretary to mark non-voters absent', function () {
    actingAs($this->secretaryUser);
    
    // Mark some members as attending
    $attendingMembers = $this->members->take(5);
    foreach ($attendingMembers as $member) {
        $this->meeting->attendances()->create(['user_id' => $member->user_id]);
    }
    
    // Create a question and have some members vote
    $question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'type' => 'Dalyvių dauguma',
        'presenter_id' => $this->chairman->user_id,
    ]);
    
    // Only first 2 members vote
    $votingMembers = $attendingMembers->take(2);
    foreach ($votingMembers as $member) {
        Vote::factory()->create([
            'question_id' => $question->question_id,
            'user_id' => $member->user_id,
            'choice' => 'Už',
        ]);
    }

    $response = $this->from(route('meetings.show', $this->meeting))
        ->post(route('attendance.mark-non-voters-absent', ['meeting' => $this->meeting->meeting_id]), [
            '_token' => csrf_token(),
        ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    // Check that non-voting members are no longer attending
    $nonVotingMembers = $attendingMembers->skip(2);
    foreach ($nonVotingMembers as $member) {
        $this->assertDatabaseMissing('meeting_attendances', [
            'meeting_id' => $this->meeting->meeting_id,
            'user_id' => $member->user_id,
        ]);
    }
    
    // Check that voting members are still attending
    foreach ($votingMembers as $member) {
        $this->assertDatabaseHas('meeting_attendances', [
            'meeting_id' => $this->meeting->meeting_id,
            'user_id' => $member->user_id,
        ]);
    }
});

it('auto-marks user as attending when they vote', function () {
    actingAs($this->members->first());
    
    $question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'type' => 'Dalyvių dauguma',
        'presenter_id' => $this->chairman->user_id,
    ]);
    
    // Add some attendees to reach quorum (need 5 out of 10)
    foreach ($this->members->skip(1)->take(5) as $member) {
        $this->meeting->attendances()->create(['user_id' => $member->user_id]);
    }
    
    // Ensure user is not initially attending
    $this->assertDatabaseMissing('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $this->members->first()->user_id,
    ]);

    // Vote
    $response = $this->from(route('meetings.show', $this->meeting))
        ->put(route('votes.store', ['meeting' => $this->meeting->meeting_id, 'question' => $question->question_id]), [
            '_token' => csrf_token(),
            'choice' => 'Už',
        ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHasNoErrors();
    
    // Check that vote was created
    $this->assertDatabaseHas('votes', [
        'question_id' => $question->question_id,
        'user_id' => $this->members->first()->user_id,
        'choice' => 'Už',
    ]);
    
    // Check that user is now marked as attending
    $this->assertDatabaseHas('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $this->members->first()->user_id,
    ]);
});

it('auto-marks user as absent when they delete their last vote', function () {
    $member = $this->members->first();
    actingAs($member);
    
    // Mark user as attending
    $this->meeting->attendances()->create(['user_id' => $member->user_id]);
    
    $question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'type' => 'Dalyvių dauguma',
        'presenter_id' => $this->chairman->user_id,
    ]);
    
    // Create a vote
    $vote = Vote::factory()->create([
        'question_id' => $question->question_id,
        'user_id' => $member->user_id,
        'choice' => 'Už',
    ]);

    // Delete the vote
    $response = $this->delete(route('votes.destroy', ['meeting' => $this->meeting->meeting_id, 'question' => $question->question_id]), [
        '_token' => csrf_token(),
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    
    // Check that user is now marked as absent
    $this->assertDatabaseMissing('meeting_attendances', [
        'meeting_id' => $this->meeting->meeting_id,
        'user_id' => $member->user_id,
    ]);
});
