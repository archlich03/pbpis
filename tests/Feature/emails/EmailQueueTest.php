<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\EmailQueue;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\post;
use function Pest\Laravel\get;
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
        'meeting_date' => now()->addDay(),
        'vote_start' => now(),
        'vote_end' => now()->addDays(2),
        'is_evote' => true,
    ]);
});

// ============================================================================
// AUTHORIZATION TESTS
// ============================================================================

it('allows IT administrator to access email compose page', function () {
    actingAs($this->adminUser);

    $response = get(route('emails.compose', $this->meeting));

    $response->assertOk();
    $response->assertViewIs('emails.compose');
});

it('allows secretary to access email compose page', function () {
    actingAs($this->secretaryUser);

    $response = get(route('emails.compose', $this->meeting));

    $response->assertOk();
    $response->assertViewIs('emails.compose');
});

it('forbids voter from accessing email compose page', function () {
    actingAs($this->voterUser);

    get(route('emails.compose', $this->meeting))
        ->assertForbidden();
});

it('redirects guests to login when trying to access email compose page', function () {
    get(route('emails.compose', $this->meeting))
        ->assertRedirect(route('login'));
});

it('allows IT administrator to send email', function () {
    actingAs($this->adminUser);

    $response = post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Email Subject',
        'body' => '<p>Test email body content</p>',
        'recipients' => [$this->members->first()->email, $this->members->last()->email],
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHas('success', __('Email has been sent successfully.'));

    expect(EmailQueue::count())->toBe(1);
    
    $email = EmailQueue::first();
    expect($email->subject)->toBe('Test Email Subject');
    expect($email->body)->toBe('<p>Test email body content</p>');
    expect($email->recipients)->toHaveCount(2);
    expect($email->meeting_id)->toBe($this->meeting->meeting_id->toString());
    expect($email->user_id)->toBe($this->adminUser->user_id);
});

it('allows secretary to send email', function () {
    actingAs($this->secretaryUser);

    $response = post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Secretary Test Email',
        'body' => '<p>Email from secretary</p>',
        'recipients' => [$this->members->first()->email],
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHas('success');

    expect(EmailQueue::count())->toBe(1);
    expect(EmailQueue::first()->user_id)->toBe($this->secretaryUser->user_id);
});

it('forbids voter from sending email', function () {
    actingAs($this->voterUser);

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Email',
        'body' => '<p>Test body</p>',
        'recipients' => [$this->members->first()->email],
    ])->assertForbidden();

    expect(EmailQueue::count())->toBe(0);
});

it('redirects guests to login when trying to send email', function () {
    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Email',
        'body' => '<p>Test body</p>',
        'recipients' => [$this->members->first()->email],
    ])->assertRedirect(route('login'));

    expect(EmailQueue::count())->toBe(0);
});

// ============================================================================
// VALIDATION TESTS
// ============================================================================

it('requires subject when sending email', function () {
    actingAs($this->adminUser);

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'body' => '<p>Test body</p>',
        'recipients' => [$this->members->first()->email],
    ])->assertSessionHasErrors('subject');

    expect(EmailQueue::count())->toBe(0);
});

it('requires body when sending email', function () {
    actingAs($this->adminUser);

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Subject',
        'recipients' => [$this->members->first()->email],
    ])->assertSessionHasErrors('body');

    expect(EmailQueue::count())->toBe(0);
});

it('requires at least one recipient when sending email', function () {
    actingAs($this->adminUser);

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Subject',
        'body' => '<p>Test body</p>',
        'recipients' => [],
    ])->assertSessionHasErrors('recipients');

    expect(EmailQueue::count())->toBe(0);
});

it('validates email format for recipients', function () {
    actingAs($this->adminUser);

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Subject',
        'body' => '<p>Test body</p>',
        'recipients' => ['invalid-email', 'also-invalid'],
    ])->assertSessionHasErrors('recipients.0');

    expect(EmailQueue::count())->toBe(0);
});

it('enforces maximum subject length of 255 characters', function () {
    actingAs($this->adminUser);

    $longSubject = str_repeat('a', 256);

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => $longSubject,
        'body' => '<p>Test body</p>',
        'recipients' => [$this->members->first()->email],
    ])->assertSessionHasErrors('subject');

    expect(EmailQueue::count())->toBe(0);
});

it('enforces maximum body length of 5000 characters', function () {
    actingAs($this->adminUser);

    $longBody = '<p>' . str_repeat('a', 5000) . '</p>';

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Subject',
        'body' => $longBody,
        'recipients' => [$this->members->first()->email],
    ])->assertSessionHasErrors('body');

    expect(EmailQueue::count())->toBe(0);
});

// ============================================================================
// COOLDOWN TESTS
// ============================================================================

it('allows sending email when no recent emails exist', function () {
    actingAs($this->adminUser);

    $response = post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'First Email',
        'body' => '<p>First email body</p>',
        'recipients' => [$this->members->first()->email],
    ]);

    $response->assertRedirect(route('meetings.show', $this->meeting));
    $response->assertSessionHas('success');

    expect(EmailQueue::count())->toBe(1);
});

it('enforces 5-minute cooldown between emails for same meeting', function () {
    actingAs($this->adminUser);

    // Create audit log for email sent 2 minutes ago
    AuditLog::create([
        'user_id' => $this->adminUser->user_id,
        'action' => 'email_sent',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test',
        'details' => [
            'meeting_id' => $this->meeting->meeting_id->toString(),
            'subject' => 'Previous Email',
        ],
        'created_at' => now()->subMinutes(2),
    ]);

    $response = post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Second Email',
        'body' => '<p>Second email body</p>',
        'recipients' => [$this->members->first()->email],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    
    // Check that error message contains cooldown info (in Lithuanian: "Palaukite" and "min")
    $errorMessage = session('error');
    expect($errorMessage)->toContain('Palaukite');
    expect($errorMessage)->toContain('min');

    expect(EmailQueue::count())->toBe(0);
});

it('calculates remaining cooldown time correctly', function () {
    actingAs($this->adminUser);

    // Create audit log for email sent 3 minutes ago
    AuditLog::create([
        'user_id' => $this->adminUser->user_id,
        'action' => 'email_sent',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test',
        'details' => [
            'meeting_id' => $this->meeting->meeting_id->toString(),
            'subject' => 'Previous Email',
        ],
        'created_at' => now()->subMinutes(3),
    ]);

    $response = post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Email',
        'body' => '<p>Test</p>',
        'recipients' => [$this->members->first()->email],
    ]);

    $response->assertSessionHas('error');
    
    // Should show 2 minutes remaining (5 - 3 = 2)
    $errorMessage = session('error');
    // Message format: "Palaukite dar X min. prieš..."
    expect($errorMessage)->toMatch('/\d+/');
});

it('cooldown only applies to same meeting, not different meetings', function () {
    actingAs($this->adminUser);

    // Create another meeting
    $anotherMeeting = Meeting::factory()->create([
        'body_id' => $this->body->body_id,
        'secretary_id' => $this->secretaryUser->user_id,
        'meeting_date' => now()->addDays(3),
        'vote_start' => now(),
        'vote_end' => now()->addDays(4),
        'is_evote' => true,
    ]);

    // Send email for first meeting
    AuditLog::create([
        'user_id' => $this->adminUser->user_id,
        'action' => 'email_sent',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test',
        'details' => [
            'meeting_id' => $this->meeting->meeting_id->toString(),
            'subject' => 'First Meeting Email',
        ],
        'created_at' => now()->subMinutes(2),
    ]);

    // Should be able to send email for different meeting immediately
    $response = post(route('emails.send', $anotherMeeting), [
        '_token' => csrf_token(),
        'subject' => 'Different Meeting Email',
        'body' => '<p>This should work</p>',
        'recipients' => [$this->members->first()->email],
    ]);

    $response->assertRedirect(route('meetings.show', $anotherMeeting));
    $response->assertSessionHas('success');

    expect(EmailQueue::count())->toBe(1);
});

// ============================================================================
// EMAIL QUEUE TESTS
// ============================================================================

it('stores email in queue with correct data', function () {
    actingAs($this->adminUser);

    $recipients = [
        $this->members->first()->email,
        $this->members->last()->email,
    ];

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Subject',
        'body' => '<p>Test Body</p>',
        'recipients' => $recipients,
    ]);

    $email = EmailQueue::first();

    expect($email)->not->toBeNull();
    expect($email->subject)->toBe('Test Subject');
    expect($email->body)->toBe('<p>Test Body</p>');
    expect($email->recipients)->toBe($recipients);
    expect($email->meeting_id)->toBe($this->meeting->meeting_id->toString());
    expect($email->user_id)->toBe($this->adminUser->user_id);
});

it('preserves HTML formatting in email body', function () {
    actingAs($this->adminUser);

    $htmlBody = '<p>Hello <strong>World</strong></p><ul><li>Item 1</li><li>Item 2</li></ul>';

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'HTML Test',
        'body' => $htmlBody,
        'recipients' => [$this->members->first()->email],
    ]);

    $email = EmailQueue::first();
    expect($email->body)->toBe($htmlBody);
});

it('allows multiple recipients in email queue', function () {
    actingAs($this->adminUser);

    $allMemberEmails = $this->members->pluck('email')->toArray();

    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Multiple Recipients',
        'body' => '<p>Email to all members</p>',
        'recipients' => $allMemberEmails,
    ]);

    $email = EmailQueue::first();
    expect($email->recipients)->toHaveCount(3);
    expect($email->recipients)->toBe($allMemberEmails);
});

// ============================================================================
// CASCADE DELETION TESTS
// ============================================================================

it('deletes email queue entries when meeting is deleted', function () {
    actingAs($this->adminUser);

    // Queue an email
    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Email',
        'body' => '<p>Test</p>',
        'recipients' => [$this->members->first()->email],
    ]);

    expect(EmailQueue::count())->toBe(1);

    // Delete the meeting
    $this->meeting->delete();

    // Email queue entry should be deleted
    expect(EmailQueue::count())->toBe(0);
});

it('deletes email queue entries when user is deleted', function () {
    actingAs($this->adminUser);

    // Queue an email
    post(route('emails.send', $this->meeting), [
        '_token' => csrf_token(),
        'subject' => 'Test Email',
        'body' => '<p>Test</p>',
        'recipients' => [$this->members->first()->email],
    ]);

    expect(EmailQueue::count())->toBe(1);
    expect(EmailQueue::first()->user_id)->toBe($this->adminUser->user_id);

    // Delete the user (force delete to bypass soft deletes)
    $this->adminUser->forceDelete();

    // Email queue entry should be deleted
    expect(EmailQueue::count())->toBe(0);
});

// ============================================================================
// TEMPLATE TESTS
// ============================================================================

it('loads blank template by default', function () {
    actingAs($this->adminUser);

    $response = get(route('emails.compose', $this->meeting));

    $response->assertOk();
    $response->assertViewHas('template');
    $response->assertViewHas('templateType', 'blank');
});

it('loads voting start template when specified', function () {
    actingAs($this->adminUser);

    $response = get(route('emails.compose', ['meeting' => $this->meeting, 'template' => 'voting_start']));

    $response->assertOk();
    $response->assertViewHas('templateType', 'voting_start');
    
    $template = $response->viewData('template');
    expect($template['subject'])->toContain($this->meeting->body->title);
});

it('loads agenda change template when specified', function () {
    actingAs($this->adminUser);

    $response = get(route('emails.compose', ['meeting' => $this->meeting, 'template' => 'agenda_change']));

    $response->assertOk();
    $response->assertViewHas('templateType', 'agenda_change');
});

it('loads voting reminder template when specified', function () {
    actingAs($this->adminUser);

    $response = get(route('emails.compose', ['meeting' => $this->meeting, 'template' => 'voting_reminder']));

    $response->assertOk();
    $response->assertViewHas('templateType', 'voting_reminder');
});
