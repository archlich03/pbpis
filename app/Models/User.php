<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        'ms_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'password_change_required',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'password_change_required' => 'boolean',
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
    
    /**
     * Determine gender based on Lithuanian name patterns.
     * Analyzes the last name (surname) to determine if it's typically male or female.
     * 
     * @param string $fullName The full name to analyze
     * @return int 0 for female, 1 for male (defaults to 1 if uncertain)
     */
    public static function detectGenderFromLithuanianName(string $fullName): int
    {
        // Split the name to get the last name (surname)
        $nameParts = explode(' ', trim($fullName));
        $lastName = end($nameParts);
        
        if (empty($lastName)) {
            return 1; // Default to male if no last name
        }
        
        $lastName = strtolower($lastName);
        
        // Common Lithuanian female surname endings
        $femaleEndings = [
            'ienė',     // married women (most common)
            'aitė',     // unmarried women
            'ytė',      // unmarried women
            'utė',      // unmarried women
            'ūtė',      // unmarried women
            'iūtė',     // unmarried women
            'ėtė',      // unmarried women
            'otė',      // unmarried women
            'akė',      // some surnames
            'ckė',      // some surnames
            'skė',      // some surnames
            'nė',       // some surnames
        ];
        
        // Common Lithuanian male surname endings
        $maleEndings = [
            'as',       // most common male ending
            'is',       // common male ending
            'us',       // common male ending
            'ys',       // common male ending
            'ius',      // common male ending
            'auskas',   // specific male pattern
            'inskas',   // specific male pattern
            'owski',    // Polish-origin surnames
            'evičius',  // patronymic surnames
            'avičius',  // patronymic surnames
        ];
        
        // Check for female endings first (more specific)
        foreach ($femaleEndings as $ending) {
            if (str_ends_with($lastName, $ending)) {
                return 0; // Female
            }
        }
        
        // Check for male endings
        foreach ($maleEndings as $ending) {
            if (str_ends_with($lastName, $ending)) {
                return 1; // Male
            }
        }
        
        // Additional checks for common patterns
        // If surname ends with 'a' but not in female endings, likely female
        if (str_ends_with($lastName, 'a') && !str_ends_with($lastName, 'auskas')) {
            return 0; // Female
        }
        
        // If surname ends with consonant, likely male
        $lastChar = substr($lastName, -1);
        if (in_array($lastChar, ['s', 't', 'n', 'r', 'l', 'k', 'g', 'p', 'b', 'd', 'v', 'z', 'ž', 'š', 'č'])) {
            return 1; // Male
        }
        
        // Default to male if uncertain
        return 1;
    }
    
    /**
     * Check if user has 2FA enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return !empty($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }
    
    /**
     * Check if user can use 2FA (non-Microsoft accounts only).
     */
    public function canUseTwoFactor(): bool
    {
        return empty($this->ms_id);
    }
}


