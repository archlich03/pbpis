<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Body extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'body_id';

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
     * Get the chairman of the body.
     */
    public function chairman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chairman_id');
    }
}

