<?php

namespace App\Http\Requests\Auth;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // Hit the rate limiter with 30-minute decay (1800 seconds)
            RateLimiter::hit($this->throttleKey(), 1800);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        
        $user = Auth::user();
        
        // Check if user has 2FA enabled
        if ($user->hasTwoFactorEnabled()) {
            // Store user ID in session for 2FA verification
            session([
                'pending_2fa_user_id' => $user->user_id,
                'pending_2fa_remember' => $this->boolean('remember'),
            ]);
            
            // Logout temporarily until 2FA is verified
            Auth::logout();
            
            // Redirect to 2FA verification will be handled by the controller
            return;
        }
        
        // Log successful login for non-2FA users
        AuditLog::log(
            $user->user_id,
            'login',
            $this->ip(),
            $this->userAgent(),
            ['method' => 'password']
        );
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        // Check for 10 attempts within 30 minutes (1800 seconds)
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 10)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());
        $minutes = ceil($seconds / 60);

        throw ValidationException::withMessages([
            'email' => __('Too many login attempts. Please try again in :minutes minutes.', [
                'minutes' => $minutes,
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
