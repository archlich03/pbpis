<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'question_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'meeting_id',
        'title',
        'decision',
        'presenter_id',
        'type',
        'summary',
        'position',
    ];

    /**
     * Define available statuses.
     *
     * @var array
     */
    public const STATUSES = [
        'Nebalsuoti',
        'Balsuoti dauguma', // Member majority
    ];

    public const MINIMUM_VOTES = [
        0,      // No voting needed
        (1/2),  // Member majority (>50% of all body members)
    ];



    /**
     * Get the meeting that owns the Question
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    /**
     * Get the presenter of the question.
     */
    public function presenter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }

    /**
     * Get the votes for the question.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'question_id');
    }

    
    /**
     * Get the vote for the question that's been cast by $user.
     *
     * @param User $user
     * @return \App\Models\Vote|null
     */
    public function voteByUser(User $user): ?Vote
    {
        return $this->votes()->where('user_id', $user->user_id)->first();
    }
}

