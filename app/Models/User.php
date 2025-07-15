<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'role',
        'pedagogical_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the authenticated user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'IT administratorius';
    }

    /**
     * Check if the authenticated user is a secretary.
     */
    public function isSecretary(): bool
    {
        return $this->role === 'Sekretorius';
    }

    /**
     * Check if the authenticated user is a voter.
     */
    public function isVoter(): bool
    {
        return $this->role === 'Balsuojantysis';
    }

        /**
     * Check if the authenticated user is an administrator or secretary.
     */
    public function isPrivileged(): bool
    {
        return in_array($this->role, ['IT administratorius', 'Sekretorius']);
    }
}


