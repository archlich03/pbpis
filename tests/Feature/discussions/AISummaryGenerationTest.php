<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Discussion;
use App\Models\AuditLog;
use App\Services\GeminiAIService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\post;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

beforeEach(function () {
    Session::start();

    // Set up test API key
    Config::set('services.gemini.api_key', 'test-api-key');
    Config::set('services.gemini.max_requests_per_day', 10);

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
        'vote_start' => now()->subDay()->toDateTimeString(),
        'vote_end' => now()->subHour()->toDateTimeString(),
        'status' => 'Baigtas', // Finished meeting
    ]);

    $this->question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test Question',
        'decision' => 'Test Decision',
        'presenter_id' => $this->members->first()->user_id,
        'type' => 'Balsuoti dauguma',
        'summary' => null,
    ]);
});

test('only discussions with AI consent are included in summary generation', function () {
    // Create discussions with mixed consent states
    $discussion1 = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'content' => 'First comment - included',
        'ai_consent' => true,
        'created_at' => now()->subMinutes(30),
    ]);

    $discussion2 = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[1]->user_id,
        'content' => 'Second comment - excluded',
        'ai_consent' => false,
        'created_at' => now()->subMinutes(20),
    ]);

    $discussion3 = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[2]->user_id,
        'content' => 'Third comment - included',
        'ai_consent' => true,
        'created_at' => now()->subMinutes(10),
    ]);

    // Mock the Gemini service
    $mockService = mock(GeminiAIService::class);
    $mockService->shouldReceive('generateMeetingSummary')
        ->once()
        ->withArgs(function ($comments, $questionTitle) use ($discussion1, $discussion3) {
            // Verify only comments with ai_consent=true are passed
            expect($comments)->toHaveCount(2);
            expect($comments[0]['content'])->toBe('First comment - included');
            expect($comments[1]['content'])->toBe('Third comment - included');
            // Verify they're in chronological order (oldest first)
            return true;
        })
        ->andReturn([
            'success' => true,
            'summary' => 'Generated summary from consented comments',
            'error' => null,
        ]);

    $mockService->shouldReceive('truncateSummary')
        ->once()
        ->andReturn('Generated summary from consented comments');

    actingAs($this->secretaryUser);

    $response = post(route('discussions.generateAISummary', [
        $this->meeting,
        $this->question
    ]));

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHas('success');

    $this->question->refresh();
    expect($this->question->summary)->toBe('Generated summary from consented comments');
});

test('comments are passed to AI in chronological order (oldest first)', function () {
    // Create discussions with specific timestamps
    $discussion1 = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'content' => 'Oldest comment',
        'ai_consent' => true,
        'created_at' => now()->subMinutes(60),
    ]);

    $discussion2 = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[1]->user_id,
        'content' => 'Middle comment',
        'ai_consent' => true,
        'created_at' => now()->subMinutes(30),
    ]);

    $discussion3 = Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[2]->user_id,
        'content' => 'Newest comment',
        'ai_consent' => true,
        'created_at' => now()->subMinutes(10),
    ]);

    // Mock the Gemini service
    $mockService = mock(GeminiAIService::class);
    $mockService->shouldReceive('generateMeetingSummary')
        ->once()
        ->withArgs(function ($comments, $questionTitle) {
            // Verify chronological order
            expect($comments[0]['content'])->toBe('Oldest comment');
            expect($comments[1]['content'])->toBe('Middle comment');
            expect($comments[2]['content'])->toBe('Newest comment');
            return true;
        })
        ->andReturn([
            'success' => true,
            'summary' => 'Sequential summary',
            'error' => null,
        ]);

    $mockService->shouldReceive('truncateSummary')
        ->once()
        ->andReturn('Sequential summary');

    actingAs($this->secretaryUser);

    post(route('discussions.generateAISummary', [
        $this->meeting,
        $this->question
    ]));
});

test('generation fails when no comments have AI consent', function () {
    // Create discussions without consent
    Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'content' => 'Comment without consent',
        'ai_consent' => false,
    ]);

    actingAs($this->secretaryUser);

    $response = post(route('discussions.generateAISummary', [
        $this->meeting,
        $this->question
    ]));

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHas('error');

    $this->question->refresh();
    expect($this->question->summary)->toBeNull();
});

test('generation fails when meeting is not finished', function () {
    // Update meeting to ongoing status
    $this->meeting->update(['status' => 'Vyksta']);

    Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'content' => 'Comment with consent',
        'ai_consent' => true,
    ]);

    actingAs($this->secretaryUser);

    $response = post(route('discussions.generateAISummary', [
        $this->meeting,
        $this->question
    ]));

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHas('error');
});

test('voter cannot generate AI summary', function () {
    Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'content' => 'Comment with consent',
        'ai_consent' => true,
    ]);

    actingAs($this->voterUser);

    $response = post(route('discussions.generateAISummary', [
        $this->meeting,
        $this->question
    ]));

    $response->assertStatus(403);
});

test('daily rate limit blocks generation when limit reached', function () {
    // Create 10 audit log entries for today
    $maxLimit = config('services.gemini.max_requests_per_day');
    for ($i = 0; $i < $maxLimit; $i++) {
        AuditLog::create([
            'user_id' => $this->secretaryUser->user_id,
            'action' => 'ai_summary_generated',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'details' => json_encode(['test' => true]),
            'created_at' => now(),
        ]);
    }

    Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'content' => 'Comment with consent',
        'ai_consent' => true,
    ]);

    actingAs($this->secretaryUser);

    $response = post(route('discussions.generateAISummary', [
        $this->meeting,
        $this->question
    ]));

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHas('error');
});

test('daily rate limit allows generation when under limit', function () {
    // Create only 5 audit log entries (under limit of 10)
    for ($i = 0; $i < 5; $i++) {
        AuditLog::create([
            'user_id' => $this->secretaryUser->user_id,
            'action' => 'ai_summary_generated',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'details' => json_encode(['test' => true]),
            'created_at' => now(),
        ]);
    }

    Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'content' => 'Comment with consent',
        'ai_consent' => true,
    ]);

    // Mock the Gemini service
    $mockService = mock(GeminiAIService::class);
    $mockService->shouldReceive('generateMeetingSummary')
        ->once()
        ->andReturn([
            'success' => true,
            'summary' => 'Generated summary',
            'error' => null,
        ]);

    $mockService->shouldReceive('truncateSummary')
        ->once()
        ->andReturn('Generated summary');

    actingAs($this->secretaryUser);

    $response = post(route('discussions.generateAISummary', [
        $this->meeting,
        $this->question
    ]));

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHas('success');
});

test('AI generation failure is logged to audit log', function () {
    Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'content' => 'Comment with consent',
        'ai_consent' => true,
    ]);

    // Mock the Gemini service to return failure
    $mockService = mock(GeminiAIService::class);
    $mockService->shouldReceive('generateMeetingSummary')
        ->once()
        ->andReturn([
            'success' => false,
            'summary' => null,
            'error' => 'API rate limit exceeded',
        ]);

    actingAs($this->secretaryUser);

    $response = post(route('discussions.generateAISummary', [
        $this->meeting,
        $this->question
    ]));

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHas('error');

    // Verify failure was logged
    $failureLog = AuditLog::where('action', 'ai_summary_failed')
        ->where('user_id', $this->secretaryUser->user_id)
        ->latest()
        ->first();

    expect($failureLog)->not->toBeNull();
    $details = is_array($failureLog->details) ? $failureLog->details : json_decode($failureLog->details, true);
    expect($details['error'])->toBe('API rate limit exceeded');
});

test('AI generation success is logged to audit log', function () {
    Discussion::factory()->create([
        'question_id' => $this->question->question_id,
        'user_id' => $this->members[0]->user_id,
        'content' => 'Comment with consent',
        'ai_consent' => true,
    ]);

    // Mock the Gemini service
    $mockService = mock(GeminiAIService::class);
    $mockService->shouldReceive('generateMeetingSummary')
        ->once()
        ->andReturn([
            'success' => true,
            'summary' => 'Generated summary',
            'error' => null,
        ]);

    $mockService->shouldReceive('truncateSummary')
        ->once()
        ->andReturn('Generated summary');

    actingAs($this->secretaryUser);

    post(route('discussions.generateAISummary', [
        $this->meeting,
        $this->question
    ]));

    // Verify success was logged
    $successLog = AuditLog::where('action', 'ai_summary_generated')
        ->where('user_id', $this->secretaryUser->user_id)
        ->latest()
        ->first();

    expect($successLog)->not->toBeNull();
    $details = is_array($successLog->details) ? $successLog->details : json_decode($successLog->details, true);
    expect($details['question_id'])->toBe($this->question->question_id);
    expect($details['comments_count'])->toBe(1);
});
