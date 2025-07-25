<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;


class Meeting extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'meeting_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'secretary_id',
        'body_id',
        'is_evote',
        'meeting_date',
        'vote_start',
        'vote_end',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
        'vote_start'  => 'datetime',
        'vote_end'    => 'datetime',
    ];

    /**
     * Define available statuses.
     *
     * @var array
     */
    public const STATUSES = [
        'Suplanuotas',
        'Vyksta',
        'Baigtas',
    ];

    /**
     * Get the chairman of the meeting.
     */
    public function secretary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary_id');
    }

    /**
     * Get the body that owns the Meeting
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function body(): BelongsTo
    {
        return $this->belongsTo(Body::class, 'body_id');
    }

    /**
     * Get all of the questions for the Meeting
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'meeting_id')->orderBy('position');
    }

    /**
     * Get all attendance records for the Meeting
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class, 'meeting_id', 'meeting_id');
    }

    /**
     * Get all attendees (users) for the Meeting
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function attendees()
    {
        return $this->belongsToMany(User::class, 'meeting_attendances', 'meeting_id', 'user_id', 'meeting_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Check if a user is attending this meeting
     *
     * @param User $user
     * @return bool
     */
    public function isUserAttending(User $user): bool
    {
        return $this->attendees()->where('users.user_id', $user->user_id)->exists();
    }

    /**
     * Get count of attendees
     *
     * @return int
     */
    public function getAttendeesCount(): int
    {
        return $this->attendees()->count();
    }

    /**
     * Check if quorum is reached for the meeting
     *
     * @return bool
     */
    public function hasQuorum(): bool
    {
        $totalMembers = $this->body->members->count();
        $attendees = $this->getAttendeesCount();
        
        // Quorum is at least half of all body members
        return $attendees >= ($totalMembers / 2);
    }

    /**
     * Calculate the required votes for a question to pass
     *
     * @param Question $question
     * @param bool $withChairmanVote Whether chairman's vote is included
     * @return int
     */
    public function getRequiredVotesForQuestion(Question $question, bool $withChairmanVote = false): int
    {
        $totalMembers = $this->body->members->count();
        
        if ($question->type === 'Nebalsuoti') {
            return 0;
        }
        
        if ($question->type === '2/3 dauguma') {
            // 2/3 majority
            $required = ceil($totalMembers * 2 / 3);
        } else {
            // Member majority: more than half (same regardless of chairman vote)
            $required = ceil($totalMembers / 2);
        }
        
        return $required;
    }

    /**
     * Check if chairman has voted on a question
     *
     * @param Question $question
     * @return bool
     */
    public function hasChairmanVoted(Question $question): bool
    {
        $chairman = $this->body->chairman;
        if (!$chairman) {
            return false;
        }
        
        return $question->votes()->where('user_id', $chairman->user_id)->exists();
    }

    /**
     * Get current vote counts for a question
     *
     * @param Question $question
     * @return array
     */
    public function getVoteCounts(Question $question): array
    {
        $votes = $question->votes;
        
        return [
            'Už' => $votes->where('choice', 'Už')->count(),
            'Prieš' => $votes->where('choice', 'Prieš')->count(),
            'Susilaikė' => $votes->where('choice', 'Susilaiko')->count(),
        ];
    }

    /**
     * Calculate if question would pass with current votes
     *
     * @param Question $question
     * @return bool
     */
    public function calculateQuestionResult(Question $question): bool
    {
        $totalMembers = $this->body->members->count();
        $attendeesCount = $this->getAttendeesCount();
        $voteCounts = $this->getVoteCounts($question);
        $forVotes = $voteCounts['Už'];

        $againstVotes = $attendeesCount - $forVotes;

        if ($question->type === 'Nebalsuoti') {
            return true;
        } else {
            $requiredVotes = 0;
            if ($question->type === '2/3 dauguma')
                $requiredVotes = $totalMembers * 2 / 3;
            else if ($question->type === 'Balsuoti dauguma')
                $requiredVotes = $totalMembers / 2;

            // Check if it passes with standard majority
            if ($forVotes > $requiredVotes) {
                return true;
            }

            // Chairman tiebreaker: only applies when exactly half vote for and half against
            // This only makes sense for even-numbered bodies
            $chairman = $this->body->chairman;
            if (
                $chairman &&
                $totalMembers % 2 == 0 &&
                $forVotes == $againstVotes &&
                $forVotes + $againstVotes > $requiredVotes
            ) {
                $chairmanVote = $question->votes()->where('user_id', $chairman->user_id)->first();
                if ($chairmanVote && $chairmanVote->choice === 'Už') {
                    // True 50-50 split: chairman breaks tie
                    return true;
                } else {
                    return false;
                }
            }

            return false;
        }
    }


    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->meeting_id)) {
                $model->meeting_id = (string) Str::uuid();
            }
        });
    }
}

