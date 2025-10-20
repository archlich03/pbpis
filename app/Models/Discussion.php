<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discussion extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'discussion_id';

    protected $fillable = [
        'question_id',
        'user_id',
        'parent_id',
        'content',
        'ai_consent',
    ];

    protected $casts = [
        'ai_consent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // When a discussion is soft deleted, also soft delete all its replies
        static::deleting(function ($discussion) {
            if ($discussion->isForceDeleting()) {
                $discussion->replies()->forceDelete();
            } else {
                $discussion->replies()->delete();
            }
        });
    }

    /**
     * Get the question this discussion belongs to.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }

    /**
     * Get the user who created this discussion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the parent discussion (if this is a reply).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Discussion::class, 'parent_id', 'discussion_id');
    }

    /**
     * Get all replies to this discussion.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Discussion::class, 'parent_id', 'discussion_id')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Check if this discussion is a reply to another discussion.
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Get the meeting this discussion belongs to (through question).
     */
    public function meeting()
    {
        return $this->question->meeting();
    }

    /**
     * Scope to get only top-level discussions (not replies).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get discussions for a specific question.
     */
    public function scopeForQuestion($query, $questionId)
    {
        return $query->where('question_id', $questionId);
    }
}
