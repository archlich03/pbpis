<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
    ];

    /**
     * Define available statuses.
     *
     * @var array
     */
    public const STATUSES = [
        'Nebalsuoti',
        'Balsuoti dauguma',
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
}

