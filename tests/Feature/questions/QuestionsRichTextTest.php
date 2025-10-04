<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\post;
use function Pest\Laravel\patch;
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
        'body_id' => $this->body->body_id,
        'secretary_id' => $this->secretaryUser->user_id,
        'is_evote' => 0,
        'meeting_date' => now()->toDateString(),
        'vote_start' => now()->toDateTimeString(),
        'vote_end' => now()->addDay()->toDateTimeString(),
        'status' => 'Suplanuotas',
    ]);
});

// Test storing questions with rich text HTML content
it('stores question with bold text in summary', function () {
    $htmlContent = '<p><strong>This is bold text</strong></p>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});

it('stores question with italic text in summary', function () {
    $htmlContent = '<p><em>This is italic text</em></p>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});

it('stores question with bullet list in summary', function () {
    $htmlContent = '<ul><li><p>First item</p></li><li><p>Second item</p></li><li><p>Third item</p></li></ul>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});

it('stores question with ordered list in summary', function () {
    $htmlContent = '<ol><li><p>First step</p></li><li><p>Second step</p></li><li><p>Third step</p></li></ol>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});

it('stores question with heading in summary', function () {
    $htmlContent = '<h1>Main Heading</h1><p>Some content</p>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});

it('stores question with blockquote in summary', function () {
    $htmlContent = '<blockquote><p>This is a quote</p></blockquote>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});

it('stores question with mixed formatting in summary', function () {
    $htmlContent = '<h2>Important Points</h2><ul><li><p><strong>Bold point</strong> with <em>italic</em></p></li><li><p>Regular point</p></li></ul><blockquote><p>A relevant quote</p></blockquote>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});

// Test updating questions with rich text
it('updates question summary with rich text content', function () {
    $question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Original question',
        'summary' => '<p>Original summary</p>',
        'type' => 'Nebalsuoti',
        'presenter_id' => $this->members->first()->user_id,
    ]);

    $newHtmlContent = '<p><strong>Updated</strong> summary with <em>formatting</em></p>';

    actingAs($this->secretaryUser)
        ->patch(route('questions.update', [$this->meeting, $question]), [
            '_token' => csrf_token(),
            'title' => 'Original question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $newHtmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'question_id' => $question->question_id,
        'summary' => $newHtmlContent,
    ]);
});

it('preserves HTML structure when updating question', function () {
    $question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Original question',
        'summary' => '<p>Original</p>',
        'type' => 'Nebalsuoti',
        'presenter_id' => $this->members->first()->user_id,
    ]);

    $complexHtml = '<h1>Title</h1><ul><li><p>Item 1</p></li><li><p>Item 2</p></li></ul><p>Paragraph with <strong>bold</strong> and <em>italic</em></p>';

    actingAs($this->secretaryUser)
        ->patch(route('questions.update', [$this->meeting, $question]), [
            '_token' => csrf_token(),
            'title' => 'Original question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $complexHtml,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $question->refresh();
    expect($question->summary)->toBe($complexHtml);
});

// Test that HTML is properly escaped/sanitized
it('stores question with special characters in summary', function () {
    $htmlContent = '<p>Text with &amp; ampersand and &lt; less than</p>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});

// Test empty and null summaries
it('stores question with empty summary', function () {
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => '',
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test question',
        'summary' => '',
    ]);
});

it('updates question to have empty summary', function () {
    $question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Original question',
        'summary' => '<p>Original summary</p>',
        'type' => 'Nebalsuoti',
        'presenter_id' => $this->members->first()->user_id,
    ]);

    actingAs($this->secretaryUser)
        ->patch(route('questions.update', [$this->meeting, $question]), [
            '_token' => csrf_token(),
            'title' => 'Original question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => '',
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $question->refresh();
    expect($question->summary)->toBe('');
});

// Test display of rich text content
it('displays rich text summary correctly on meeting show page', function () {
    $htmlContent = '<p><strong>Bold text</strong> and <em>italic text</em></p>';
    
    $question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test question',
        'summary' => $htmlContent,
        'type' => 'Nebalsuoti',
        'presenter_id' => $this->members->first()->user_id,
    ]);

    $response = actingAs($this->members->first())
        ->get(route('meetings.show', $this->meeting));

    $response->assertSee('Bold text', false); // false = don't escape HTML
    $response->assertSee('<strong>Bold text</strong>', false);
    $response->assertSee('<em>italic text</em>', false);
});

it('displays bullet list correctly on meeting show page', function () {
    $htmlContent = '<ul><li><p>First item</p></li><li><p>Second item</p></li></ul>';
    
    $question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test question',
        'summary' => $htmlContent,
        'type' => 'Nebalsuoti',
        'presenter_id' => $this->members->first()->user_id,
    ]);

    $response = actingAs($this->members->first())
        ->get(route('meetings.show', $this->meeting));

    $response->assertSee('<ul>', false);
    $response->assertSee('<li>', false);
    $response->assertSee('First item', false);
    $response->assertSee('Second item', false);
});

it('displays rich text summary correctly on protocol page', function () {
    $htmlContent = '<p><strong>Important decision</strong> with details</p>';
    
    $question = Question::factory()->create([
        'meeting_id' => $this->meeting->meeting_id,
        'title' => 'Test question',
        'summary' => $htmlContent,
        'type' => 'Nebalsuoti',
        'presenter_id' => $this->members->first()->user_id,
    ]);

    $response = actingAs($this->secretaryUser)
        ->get(route('meetings.protocol', $this->meeting));

    $response->assertSee('<strong>Important decision</strong>', false);
    $response->assertSee('Important decision', false);
});

// Test that plain text still works
it('stores question with plain text summary (backward compatibility)', function () {
    $plainText = 'This is just plain text without any HTML';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $plainText,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $plainText,
    ]);
});

// Test horizontal rule
it('stores question with horizontal rule in summary', function () {
    $htmlContent = '<p>Before line</p><hr><p>After line</p>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});

// Test multiple paragraphs
it('stores question with multiple paragraphs in summary', function () {
    $htmlContent = '<p>First paragraph</p><p>Second paragraph</p><p>Third paragraph</p>';
    
    actingAs($this->secretaryUser)
        ->post(route('questions.store', $this->meeting), [
            '_token' => csrf_token(),
            'title' => 'Test question',
            'decision' => '',
            'presenter_id' => $this->members->first()->user_id,
            'type' => 'Nebalsuoti',
            'summary' => $htmlContent,
        ])
        ->assertRedirect(route('meetings.show', $this->meeting));

    $this->assertDatabaseHas('questions', [
        'meeting_id' => $this->meeting->meeting_id,
        'summary' => $htmlContent,
    ]);
});
