<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Body extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'body_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'classification',
        'chairman_id',
        'members',
        'is_ba_sp',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the value of the model's primary key.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'body_id';
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'members' => 'array',
    ];

    /**
     * Get the chairman of the body.
     */
    public function chairman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chairman_id')->withTrashed();
    }

    public function getMembersAttribute()
    {
        $ids = $this->attributes['members'] ? json_decode($this->attributes['members'], true) : [];

        return User::withTrashed()->whereIn('user_id', $ids)->get();
    }

    /**
     * Get all of the meetings for the Body
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'body_id')->orderByDesc('meeting_date');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->body_id)) {
                $model->body_id = (string) Str::uuid();
            }
        });
    }
}


