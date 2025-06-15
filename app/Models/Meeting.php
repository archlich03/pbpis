<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

