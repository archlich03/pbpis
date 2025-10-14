<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
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
});

it('allows IT admin to view protocol HTML', function () {
    actingAs($this->adminUser);

    $response = get(route('meetings.protocol', $this->meeting->meeting_id));

    $response->assertOk()
        ->assertViewIs('meetings.protocol')
        ->assertViewHas('meeting');
});

it('allows secretary to view protocol HTML', function () {
    actingAs($this->secretaryUser);

    $response = get(route('meetings.protocol', $this->meeting->meeting_id));

    $response->assertOk()
        ->assertViewIs('meetings.protocol')
        ->assertViewHas('meeting');
});

it('denies voter access to protocol HTML', function () {
    actingAs($this->voterUser);

    get(route('meetings.protocol', $this->meeting->meeting_id))
        ->assertForbidden();
});

it('allows IT admin to download protocol PDF', function () {
    actingAs($this->adminUser);

    $response = get(route('meetings.pdf', $this->meeting->meeting_id));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('allows secretary to download protocol PDF', function () {
    actingAs($this->secretaryUser);

    $response = get(route('meetings.pdf', $this->meeting->meeting_id));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('denies voter access to protocol PDF', function () {
    actingAs($this->voterUser);

    get(route('meetings.pdf', $this->meeting->meeting_id))
        ->assertForbidden();
});

it('allows IT admin to download protocol DOCX', function () {
    actingAs($this->adminUser);

    $response = get(route('meetings.docx', $this->meeting->meeting_id));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
});

it('allows secretary to download protocol DOCX', function () {
    actingAs($this->secretaryUser);

    $response = get(route('meetings.docx', $this->meeting->meeting_id));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
});

it('denies voter access to protocol DOCX', function () {
    actingAs($this->voterUser);

    get(route('meetings.docx', $this->meeting->meeting_id))
        ->assertForbidden();
});

it('returns 404 if meeting not found for protocol', function () {
    actingAs($this->adminUser);

    get(route('meetings.protocol', 'non-existent-id'))->assertNotFound();
});

it('returns 404 if meeting not found for PDF', function () {
    actingAs($this->adminUser);

    get(route('meetings.pdf', 'non-existent-id'))->assertNotFound();
});

it('returns 404 if meeting not found for DOCX', function () {
    actingAs($this->adminUser);

    get(route('meetings.docx', 'non-existent-id'))->assertNotFound();
});
