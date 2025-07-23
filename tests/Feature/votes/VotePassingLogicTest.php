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

describe('Attendance-based voting (Dalyvių dauguma)', function () {
    beforeEach(function () {
        $this->question = Question::factory()->create([
            'meeting_id' => $this->meeting->meeting_id,
            'title' => 'Attendance-based Question',
            'type' => 'Dalyvių dauguma',
            'presenter_id' => $this->chairman->user_id,
        ]);
    });

    it('passes with simple majority of attendees - scenario 1', function () {
        // 6 attendees, 4 vote for, 2 vote against
        $attendees = $this->members->take(6);
        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 4 vote for
        foreach ($attendees->take(4) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 2 vote against
        foreach ($attendees->skip(4) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // Required: >3 votes (more than half of 6 attendees)
        // For votes: 4 >= 4 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });

    it('fails without simple majority of attendees - scenario 2', function () {
        // 8 attendees, 3 vote for, 2 vote against, 3 abstain
        $attendees = $this->members->take(8);
        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 3 vote for
        foreach ($attendees->take(3) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 2 vote against
        foreach ($attendees->skip(3)->take(2) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // 3 abstain
        foreach ($attendees->skip(5) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Susilaikė',
            ]);
        }

        // Required: >4 votes (more than half of 8 attendees)
        // For votes: 3 < 5 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($this->question))->toBeFalse();
    });

    it('passes with exact minimum required votes - scenario 3', function () {
        // 7 attendees, 4 vote for, 1 vote against, 2 abstain
        $attendees = $this->members->take(7);
        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 4 vote for
        foreach ($attendees->take(4) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 1 vote against
        Vote::factory()->create([
            'question_id' => $this->question->question_id,
            'user_id' => $attendees->skip(4)->first()->user_id,
            'choice' => 'Prieš',
        ]);

        // 2 abstain
        foreach ($attendees->skip(5) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Susilaikė',
            ]);
        }

        // Required: >3.5 votes (more than half of 7 attendees) = 4 votes minimum
        // For votes: 4 >= 4 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });

    it('fails with tie votes - scenario 4', function () {
        // 6 attendees, 3 vote for, 3 vote against
        $attendees = $this->members->take(6);
        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 3 vote for
        foreach ($attendees->take(3) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 3 vote against
        foreach ($attendees->skip(3) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // Required: >3 votes (more than half of 6 attendees) = 4 votes minimum
        // For votes: 3 < 4 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($this->question))->toBeFalse();
    });

    it('ignores votes from non-attendees - scenario 5', function () {
        // 4 attendees, 3 vote for, 1 vote against
        // 2 non-attendees also vote (should be ignored)
        $attendees = $this->members->take(4);
        $nonAttendees = $this->members->skip(4)->take(2);

        foreach ($attendees as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 3 attendees vote for
        foreach ($attendees->take(3) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 1 attendee votes against
        Vote::factory()->create([
            'question_id' => $this->question->question_id,
            'user_id' => $attendees->skip(3)->first()->user_id,
            'choice' => 'Prieš',
        ]);

        // 2 non-attendees vote for (should be ignored)
        foreach ($nonAttendees as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // Required: >2 votes (more than half of 4 attendees) = 3 votes minimum
        // For votes from attendees only: 3 >= 3 (required) -> PASSES
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
        // Body has 10 members, 6 vote for, 2 vote against, 2 don't vote
        $votingMembers = $this->members->take(8);
        
        // Mark some as attending (doesn't matter for this vote type)
        foreach ($votingMembers->take(6) as $member) {
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

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 6 >= 6 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });

    it('fails without simple majority of all body members - scenario 2', function () {
        // Body has 10 members, 4 vote for, 3 vote against, 3 don't vote
        $votingMembers = $this->members->take(7);
        
        // Mark some as attending
        foreach ($votingMembers as $member) {
            $this->meeting->attendances()->create(['user_id' => $member->user_id]);
        }

        // 4 vote for
        foreach ($votingMembers->take(4) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // 3 vote against
        foreach ($votingMembers->skip(4) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Prieš',
            ]);
        }

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes: 4 < 6 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($this->question))->toBeFalse();
    });

    it('only counts votes from attendees even for all-members voting - scenario 3', function () {
        // Body has 10 members, but only 5 are attending
        // 4 attendees vote for, 1 attendee votes against
        // 3 non-attendees vote for (should be ignored)
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

        // 3 non-attendees vote for (should be ignored)
        foreach ($nonAttendees as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // Required: >5 votes (more than half of 10 body members) = 6 votes minimum
        // For votes from attendees only: 4 < 6 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($this->question))->toBeFalse();
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
        // Body has 9 members, 6 vote for, 1 vote against, 2 abstain
        // Adjust body to have 9 members for exact 2/3 calculation
        $this->body->members = $this->members->take(9)->pluck('user_id')->toArray();
        $this->body->save();

        $votingMembers = $this->members->take(9);
        
        // Mark all as attending
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

        // 1 vote against
        Vote::factory()->create([
            'question_id' => $this->question->question_id,
            'user_id' => $votingMembers->skip(6)->first()->user_id,
            'choice' => 'Prieš',
        ]);

        // 2 abstain
        foreach ($votingMembers->skip(7) as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Susilaikė',
            ]);
        }

        // Required: >=6 votes (2/3 of 9 body members) = exactly 6 votes
        // For votes: 6 >= 6 (required) -> PASSES
        expect($this->meeting->calculateQuestionResult($this->question))->toBeTrue();
    });

    it('only counts votes from attendees for 2/3 majority - scenario 4', function () {
        // Body has 10 members, 8 are attending
        // 5 attendees vote for, 1 attendee votes against, 2 attendees abstain
        // 2 non-attendees vote for (should be ignored)
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
                'choice' => 'Susilaikė',
            ]);
        }

        // 2 non-attendees vote for (should be ignored)
        foreach ($nonAttendees as $member) {
            Vote::factory()->create([
                'question_id' => $this->question->question_id,
                'user_id' => $member->user_id,
                'choice' => 'Už',
            ]);
        }

        // Required: >=6.67 votes (2/3 of 10 body members) = 7 votes minimum
        // For votes from attendees only: 5 < 7 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($this->question))->toBeFalse();
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
                'choice' => 'Susilaikė',
            ]);
        }

        // Required: >3 votes (more than half of 6 attendees) = 4 votes minimum
        // For votes: 0 < 4 (required) -> FAILS
        expect($this->meeting->calculateQuestionResult($question))->toBeFalse();
    });
});
