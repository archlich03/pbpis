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
     * Calculate if question passes based on voting type
     *
     * @param Question $question
     * @return bool
     */
    public function calculateQuestionResult(Question $question): bool
    {
        // Only count votes from attendees
        $attendeeIds = $this->attendances->pluck('user_id')->toArray();
        $votes = $question->votes->whereIn('user_id', $attendeeIds);
        
        $forVotes = $votes->where('choice', 'Už')->count();
        $requiredVotes = $this->getRequiredVotesForQuestion($question);
        
        // Question passes if "For" votes meet or exceed the required threshold
        return $forVotes >= $requiredVotes;
    }

    /**
     * Get the required number of "For" votes for a question to pass
     *
     * @param Question $question
     * @return float
     */
    public function getRequiredVotesForQuestion(Question $question): float
    {
        if ($question->isAttendanceBasedVoting()) {
            // For attendance-based voting, need more than half of attendees
            $attendees = $this->getAttendeesCount();
            return ($attendees / 2) + 0.1; // More than half
        } else {
            // For all members voting, need more than half of all body members
            $allMembers = $this->body->members->count();
            
            if ($question->type === '2/3 dauguma') {
                return ($allMembers * 2 / 3); // 2/3 of all members
            } else {
                return ($allMembers / 2) + 0.1; // More than half of all members
            }
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

