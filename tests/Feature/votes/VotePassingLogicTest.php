<?php

use App\Models\User;
use App\Models\Body;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    Session::start();

    $this->adminUser = User::factory()->create(['role' => 'IT administratorius']);
    $this->secretaryUser = User::factory()->create(['role' => 'Sekretorius']);

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

describe('Member majority voting (Balsuoti dauguma)', function () {
    beforeEach(function () {
        $this->question = Question::factory()->create([
            'meeting_id' => $this->meeting->meeting_id,
            'title' => 'Member Majority Question',
            'type' => 'Balsuoti dauguma',
            'presenter_id' => $this->chairman->user_id,
        ]);
    });

    it('passes with simple majority of body members - scenario 1', function () {
        // Body has 10 members, 6 vote for, 2 vote against
        $voters = $this->members->take(8);
        
        // 6 vote for
        foreach ($voters->take(6) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 2 vote against
        foreach ($voters->skip(6)->take(2) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 6 >= 6 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });

    it('fails without simple majority of body members - scenario 2', function () {
        // Body has 10 members, 5 vote for, 2 vote against, 3 abstain
        $voters = $this->members->take(10);
        
        // 5 vote for
        foreach ($voters->take(5) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 2 vote against
        foreach ($voters->skip(5)->take(2) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // 3 abstain
        foreach ($voters->skip(7)->take(3) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Susilaiko',
            ]);
        }

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 5 < 6 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($this->question))->toBeFalse();
    });

    it('passes with exact minimum required votes - scenario 3', function () {
        // Body has 10 members, 6 vote for, 1 vote against, 2 abstain
        $voters = $this->members->take(9);
        
        // 6 vote for (exact minimum)
        foreach ($voters->take(6) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 1 vote against
        Vote::factory()->create([
            'question_id' => $this->question->question_id,
            'user_id' => $voters->skip(6)->first()->user_id,
            'choice' => 'Prieš',
        ]);

        // 2 abstain
        foreach ($voters->skip(7)->take(2) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Susilaiko',
            ]);
        }

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 6 >= 6 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });

    it('fails with insufficient votes - scenario 4', function () {
        // Body has 10 members, 5 vote for, 4 vote against, 1 abstain
        $voters = $this->members->take(10);
        
        // 5 vote for
        foreach ($voters->take(5) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 4 vote against
        foreach ($voters->skip(5)->take(4) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // 1 abstain
        Vote::factory()->create([
            'question_id' => $this->question->question_id,
            'user_id' => $voters->skip(9)->first()->user_id,
            'choice' => 'Susilaiko',
        ]);

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 5 < 6 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($this->question))->toBeFalse();
    });

    it('counts all body member votes regardless of attendance - scenario 5', function () {
        // Body has 10 members, 7 vote for, 2 vote against
        // Some voters are not marked as attending (but votes still count)
        $voters = $this->members->take(9);
        
        // Only mark 4 as attending
        foreach ($voters->take(4) as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 7 vote for (including non-attendees)
        foreach ($voters->take(7) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 2 vote against
        foreach ($voters->skip(7)->take(2) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 7 >= 6 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });
});

describe('All members voting (Balsuoti dauguma)', function () {
    beforeEach(function () {
        $this->question = Question::factory()->create([
            'meeting_id' => $this->meeting->meeting_id,
            'title' => 'All Members Question',
            'type' => 'Balsuoti dauguma',
            'presenter_id' => $this->chairman->user_id,
        ]);
    });

    it('passes with simple majority of all body members - scenario 1', function () {
        // Body has 10 members, 6 vote for, 2 vote against, 3 don't vote
        $votingMembers = $this->members->take(8);
        
        // 6 vote for
        foreach ($votingMembers->take(6) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 2 vote against
        foreach ($votingMembers->skip(6) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 6 >= 6 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });

    it('fails without simple majority of all body members - scenario 2', function () {
        // Body has 10 members, 5 vote for, 3 vote against, 3 don't vote
        $votingMembers = $this->members->take(8);
        
        // 5 vote for
        foreach ($votingMembers->take(5) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 3 vote against
        foreach ($votingMembers->skip(5) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 5 < 6 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($this->question))->toBeFalse();
    });

    it('counts all votes including non-attendees for all-members voting - scenario 3', function () {
        // Body has 10 members, 7 total vote (4 attendees + 3 non-attendees), 1 against
        $attendees = $this->members->take(5);
        $nonAttendees = $this->members->skip(5)->take(3);

        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 4 attendees vote for
        foreach ($attendees->take(4) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 1 attendee votes against
        Vote::factory()->create([
            'question_id' => $this->question->question_id,
            'user_id' => $attendees->skip(4)->first()->user_id,
            'choice' => 'Prieš',
        ]);

        // 3 non-attendees vote for (now counted)
        foreach ($nonAttendees as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 7 >= 6 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });
});

describe('2/3 majority voting (2/3 dauguma)', function () {
    beforeEach(function () {
        $this->question = Question::factory()->create([
            'meeting_id' => $this->meeting->meeting_id,
            'title' => '2/3 Majority Question',
            'type' => '2/3 dauguma',
            'presenter_id' => $this->chairman->user_id,
        ]);
    });

    it('passes with 2/3 majority of all body members - scenario 1', function () {
        // Body has 10 members, 7 vote for, 1 vote against, 2 don't vote
        $votingMembers = $this->members->take(8);
        
        // Mark some as attending
        foreach ($votingMembers as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 7 vote for
        foreach ($votingMembers->take(7) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 1 vote against
        Vote::factory()->create([
            'question_id' => $this->question->question_id,
            'user_id' => $votingMembers->skip(7)->first()->user_id,
            'choice' => 'Prieš',
        ]);

        // Required: >=6.67 votes (2/3 of 10 body members) = 7 votes minimum
        // For votes: 7 >= 7 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });

    it('fails without 2/3 majority of all body members - scenario 2', function () {
        // Body has 10 members, 6 vote for, 2 vote against, 2 don't vote
        $votingMembers = $this->members->take(8);
        
        // Mark some as attending
        foreach ($votingMembers as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 6 vote for
        foreach ($votingMembers->take(6) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 2 vote against
        foreach ($votingMembers->skip(6) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // Required: >=6.67 votes (2/3 of 10 body members) = 7 votes minimum
        // For votes: 6 < 7 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($this->question))->toBeFalse();
    });

    it('passes with exact 2/3 majority - scenario 3', function () {
        // Body has 10 members, 8 vote for, 1 vote against, 2 abstain
        $votingMembers = $this->members->take(10);
        
        // 8 vote for (exactly 2/3 of 10 = 6.67, so need 7)
        foreach ($votingMembers->take(8) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 1 vote against
        Vote::factory()->create([
            'question_id' => $this->question->question_id,
            'user_id' => $votingMembers->skip(8)->first()->user_id,
            'choice' => 'Prieš',
        ]);

        // 2 abstain
        foreach ($votingMembers->skip(9)->take(2) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Susilaiko',
            ]);
        }

        // Required: >=6.67 votes (2/3 of 10 body members) = 7 votes minimum
        // For votes: 8 >= 7 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });

    it('counts all votes including non-attendees for 2/3 majority - scenario 4', function () {
        // Body has 10 members, 7 total vote for (5 attendees + 2 non-attendees), 1 against, 2 abstain
        $attendees = $this->members->take(8);
        $nonAttendees = $this->members->skip(8)->take(2);

        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 5 attendees vote for
        foreach ($attendees->take(5) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 1 attendee votes against
        Vote::factory()->create([
            'question_id' => $this->question->question_id,
            'user_id' => $attendees->skip(5)->first()->user_id,
            'choice' => 'Prieš',
        ]);

        // 2 attendees abstain
        foreach ($attendees->skip(6) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Susilaiko',
            ]);
        }

        // 2 non-attendees vote for (now counted)
        foreach ($nonAttendees as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // Required: >=6.67 votes (2/3 of 10 body members) = 7 votes minimum
        // For votes: 7 >= 7 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });
});

describe('Edge cases and quorum requirements', function () {
    it('fails when no quorum is reached regardless of votes', function () {
        // Body has 10 members, only 2 attending (no quorum)
        $attendees = $this->members->take(2);
        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        $question = Question::factory()->create([
            'meeting_id' => $this->meeting->meeting_id,
            'type' => 'Dalyvių dauguma',
            'presenter_id' => $this->chairman->user_id,
        ]);

        // Both attendees vote for
        foreach ($attendees as $member) {
            Vote::factory()->create([
                'question_id' => $question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // Even though all attendees voted for, no quorum means no valid result
        // But our calculateQuestionResult doesn't check quorum, it just calculates based on votes
        // The quorum check is done in the controller and display logic
        expect($this->meeting->hasQuorum())->toBeFalse();
        
        // The calculation itself would pass if we ignore quorum
        expect($this->meeting->calculateQuestionResult($question))->toBeTrue();
    });

    it('handles empty vote scenarios', function () {
        $question = Question::factory()->create([
            'meeting_id' => $this->meeting->meeting_id,
            'type' => 'Dalyvių dauguma',
            'presenter_id' => $this->chairman->user_id,
        ]);

        // 5 attendees, no votes cast
        $attendees = $this->members->take(5);
        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // Required: >2.5 votes (more than half of 5 attendees) = 3 votes minimum
        // For votes: 0 < 3 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($question))->toBeFalse();
    });

    it('handles all abstain votes scenario', function () {
        $question = Question::factory()->create([
            'meeting_id' => $this->meeting->meeting_id,
            'type' => 'Dalyvių dauguma',
            'presenter_id' => $this->chairman->user_id,
        ]);

        // 6 attendees, all abstain
        $attendees = $this->members->take(6);
        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // All abstain
        foreach ($attendees as $member) {
            Vote::factory()->create([
                'question_id' => $question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Susilaiko',
            ]);
        }

        // Required: >3 votes (more than half of 6 attendees) = 4 votes minimum
        // For votes: 0 < 4 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($question))->toBeFalse();
    });
});
