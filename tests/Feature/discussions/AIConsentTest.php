<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Discussion;
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

    $this->discussion = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->voterUser->user_id,
        'content' => 'Test discussion content',
        'ai_consent' => false,
    ]);
});

test('secretary can toggle AI consent on discussion', function () {
    actingAs($this->secretaryUser);

    expect($this->discussion->ai_consent)->toBeFalse();

    $response = post(route('discussions.toggleAIConsent', [
        $this->meeting,
        $this->question,
        $this->discussion
    ]));

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'ai_consent' => true,
    ]);

    $this->discussion->refresh();
    expect($this->discussion->ai_consent)->toBeTrue();
});

test('IT admin can toggle AI consent on discussion', function () {
    actingAs($this->adminUser);

    expect($this->discussion->ai_consent)->toBeFalse();

    $response = post(route('discussions.toggleAIConsent', [
        $this->meeting,
        $this->question,
        $this->discussion
    ]));

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'ai_consent' => true,
    ]);

    $this->discussion->refresh();
    expect($this->discussion->ai_consent)->toBeTrue();
});

test('voter cannot toggle AI consent on discussion', function () {
    actingAs($this->voterUser);

    $response = post(route('discussions.toggleAIConsent', [
        $this->meeting,
        $this->question,
        $this->discussion
    ]));

    $response->assertStatus(403);
    
    $this->discussion->refresh();
    expect($this->discussion->ai_consent)->toBeFalse();
});

test('AI consent can be toggled multiple times', function () {
    actingAs($this->secretaryUser);

    // Toggle to true
    post(route('discussions.toggleAIConsent', [
        $this->meeting,
        $this->question,
        $this->discussion
    ]));

    $this->discussion->refresh();
    expect($this->discussion->ai_consent)->toBeTrue();

    // Toggle back to false
    $response = post(route('discussions.toggleAIConsent', [
        $this->meeting,
        $this->question,
        $this->discussion
    ]));

    $response->assertJson([
        'success' => true,
        'ai_consent' => false,
    ]);

    $this->discussion->refresh();
    expect($this->discussion->ai_consent)->toBeFalse();
});

test('AI consent defaults to false for new discussions', function () {
    $newDiscussion = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->voterUser->user_id,
        'content' => 'New discussion',
    ]);

    expect($newDiscussion->ai_consent)->toBeFalse();
});

test('multiple discussions can have different AI consent states', function () {
    actingAs($this->secretaryUser);

    $discussion2 = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->voterUser->user_id,
        'content' => 'Second discussion',
        'ai_consent' => false,
    ]);

    $discussion3 = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->voterUser->user_id,
        'content' => 'Third discussion',
        'ai_consent' => false,
    ]);

    // Toggle only discussion2
    post(route('discussions.toggleAIConsent', [
        $this->meeting,
        $this->question,
        $discussion2
    ]));

    $this->discussion->refresh();
    $discussion2->refresh();
    $discussion3->refresh();

    expect($this->discussion->ai_consent)->toBeFalse();
    expect($discussion2->ai_consent)->toBeTrue();
    expect($discussion3->ai_consent)->toBeFalse();
});
