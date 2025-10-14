<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Vote;
use App\Models\MeetingAttendance;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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
        'status' => 'Baigtas',
        'vote_start' => now()->subDays(2),
        'vote_end' => now()->subDay(),
        'secretary_id' => $this->secretaryUser->user_id,
        'body_id' => $this->body->body_id,
    ]);

    // Create attendance records
    foreach ($this->members as $member) {
        MeetingAttendance::create([
            'meeting_id' => $this->meeting->meeting_id,
            'user_id' => $member->user_id,
        ]);
    }

    // Create a question with votes
    $this->question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test Question',
        'type' => 'Balsuoti dauguma',
        'position' => 1,
        'presenter_id' => $this->chairman->user_id,
    ]);

    Vote::create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'choice' => 'Už',
    ]);

    Vote::create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[1]->user_id,
        'choice' => 'Prieš',
    ]);
});

it('allows IT admin to view voting report', function () {
    actingAs($this->adminUser);

    $response = get(route('meetings.voting-report', $this->meeting->meeting_id));

    $response->assertOk()
        ->assertViewIs('meetings.voting-report')
        ->assertViewHas('meeting')
        ->assertViewHas('presentMembers')
        ->assertViewHas('questions');
});

it('allows secretary to view voting report', function () {
    actingAs($this->secretaryUser);

    $response = get(route('meetings.voting-report', $this->meeting->meeting_id));

    $response->assertOk()
        ->assertViewIs('meetings.voting-report')
        ->assertViewHas('meeting')
        ->assertViewHas('presentMembers')
        ->assertViewHas('questions');
});

it('denies voter access to voting report', function () {
    actingAs($this->voterUser);

    get(route('meetings.voting-report', $this->meeting->meeting_id))
        ->assertForbidden();
});

it('denies body member access to voting report', function () {
    actingAs($this->members[0]);

    get(route('meetings.voting-report', $this->meeting->meeting_id))
        ->assertForbidden();
});

it('returns 404 if meeting not found for voting report', function () {
    actingAs($this->adminUser);

    get(route('meetings.voting-report', 'non-existent-id'))->assertNotFound();
});

it('displays only present members in voting report', function () {
    actingAs($this->adminUser);

    $response = get(route('meetings.voting-report', $this->meeting->meeting_id));

    $response->assertOk();
    
    $presentMembers = $response->viewData('presentMembers');
    expect($presentMembers->count())->toBe(3);
});

it('sorts members alphabetically in voting report', function () {
    actingAs($this->adminUser);

    $response = get(route('meetings.voting-report', $this->meeting->meeting_id));

    $response->assertOk();
    
    $presentMembers = $response->viewData('presentMembers');
    $names = $presentMembers->pluck('name')->toArray();
    $sortedNames = $presentMembers->sortBy('name')->pluck('name')->toArray();
    
    expect($names)->toBe(array_values($sortedNames));
});
