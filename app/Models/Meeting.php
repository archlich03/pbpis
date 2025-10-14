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
     * Supports multiple voting types with different thresholds
     * 
     * Note: Voting is allowed without quorum. The quorum check is for display purposes only.
     *
     * @param Question $question
     * @return bool
     */
    public function calculateQuestionResult(Question $question): bool
    {
        if ($question->type === 'Nebalsuoti') {
            return true;
        }

        $voteCounts = $this->getVoteCounts($question);
        $votesFor = $voteCounts['Už'];
        $votesAgainst = $voteCounts['Prieš'];
        
        // Determine threshold based on voting type
        $totalMembers = $this->body->members->count();
        
        // Simple majority (Balsuoti dauguma): need > 50% of all body members
        $majorityThreshold = $totalMembers / 2;

        // Decision is adopted only if votes_for > majority_threshold
        if ($votesFor > $majorityThreshold) {
            return true; // Decision adopted
        }
        
        // Check for exact tie between "for" and "against"
        if ($votesFor == $votesAgainst) {
            // Chairman breaks the tie
            $chairman = $this->body->chairman;
            if ($chairman) {
                $chairmanVote = $question->votes()->where('user_id', $chairman->user_id)->first();
                if ($chairmanVote && $chairmanVote->choice === 'Už') {
                    return true; // Chairman voted "for" - decision adopted
                }
            }
        }
        
        return false; // Decision rejected
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

