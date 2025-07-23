<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    protected $google2fa;
    
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }
    
    /**
     * Show 2FA setup form.
     */
    public function setup(): View
    {
        $user = Auth::user();
        
        if (!$user->canUseTwoFactor()) {
            abort(403, 'Microsoft accounts cannot use 2FA');
        }
        
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')->with('error', '2FA is already enabled');
        }
        
        $secret = $this->google2fa->generateSecretKey();
        session(['2fa_secret' => $secret]);
        
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
        
        return view('two-factor.setup', [
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $secret,
        ]);
    }
    
    /**
     * Confirm 2FA setup.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);
        
        $user = Auth::user();
        $secret = session('2fa_secret');
        
        if (!$secret) {
            return redirect()->route('two-factor.setup')->with('error', 'Setup session expired');
        }
        
        $valid = $this->google2fa->verifyKey($secret, $request->code);
        
        if (!$valid) {
            return back()->withErrors(['code' => 'Invalid verification code']);
        }
        
        // Generate recovery codes
        $recoveryCodes = $user->generateRecoveryCodes();
        
        // Save 2FA settings
        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ]);
        
        // Clear session
        session()->forget('2fa_secret');
        
        // Log the setup
        AuditLog::log(
            $user->user_id,
            '2fa_setup',
            $request->ip(),
            $request->userAgent()
        );
        
        return redirect()->route('two-factor.recovery-codes')->with('status', '2fa-enabled');
    }
    
    /**
     * Show 2FA verification form during login.
     */
    public function verify(): View
    {
        if (!session('pending_2fa_user_id')) {
            return redirect()->route('login');
        }
        
        return view('two-factor.verify');
    }
    
    /**
     * Verify 2FA code during login.
     */
    public function verifyCode(Request $request): RedirectResponse
    {
        $userId = session('pending_2fa_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }
        
        // Rate limiting for 2FA attempts
        $throttleKey = 'two-factor:' . $request->ip() . ':' . $userId;
        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);
            
            throw ValidationException::withMessages([
                'code' => __('Too many 2FA attempts. Please try again in :minutes minutes.', [
                    'minutes' => $minutes,
                ]),
            ]);
        }
        
        $request->validate([
            'code' => 'required|string',
        ]);
        
        $user = \App\Models\User::where('user_id', $userId)->first();
        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('login');
        }
        
        $isValid = false;
        
        // Check if it's a TOTP code
        if (strlen($request->code) === 6 && is_numeric($request->code)) {
            $isValid = $this->google2fa->verifyKey($user->two_factor_secret, $request->code);
        }
        // Check if it's a recovery code
        else if (strlen($request->code) === 8) {
            $isValid = $user->useRecoveryCode($request->code);
        }
        
        if (!$isValid) {
            RateLimiter::hit($throttleKey, 300); // 5 minute decay
            return back()->withErrors(['code' => 'Invalid verification code']);
        }
        
        // Clear rate limiting and complete login
        RateLimiter::clear($throttleKey);
        session()->forget('pending_2fa_user_id');
        Auth::login($user, session('pending_2fa_remember', false));
        session()->forget('pending_2fa_remember');
        
        // Regenerate session
        $request->session()->regenerate();
        
        // Log successful login
        AuditLog::log(
            $user->user_id,
            'login',
            $request->ip(),
            $request->userAgent(),
            ['method' => '2fa']
        );
        
        return redirect()->intended(route('dashboard'));
    }
    
    /**
     * Disable 2FA for current user.
     */
    public function disable(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')->with('error', '2FA is not enabled');
        }
        
        // Require password confirmation for non-Microsoft accounts
        if (empty($user->ms_id)) {
            $request->validate([
                'password' => 'required|current_password',
            ]);
        }
        
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
        
        // Log the removal
        AuditLog::log(
            $user->user_id,
            '2fa_removed',
            $request->ip(),
            $request->userAgent(),
            ['removed_by' => 'self']
        );
        
        return redirect()->route('profile.edit')->with('status', '2fa-disabled');
    }
    
    /**
     * Show recovery codes.
     */
    public function showRecoveryCodes(): View|RedirectResponse
    {
        $user = Auth::user();
        
        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')->with('error', '2FA is not enabled');
        }
        
        return view('two-factor.recovery-codes', [
            'recoveryCodes' => $user->two_factor_recovery_codes,
        ]);
    }
    
    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit')->with('error', '2FA is not enabled');
        }
        
        $recoveryCodes = $user->generateRecoveryCodes();
        $user->update(['two_factor_recovery_codes' => $recoveryCodes]);
        
        return redirect()->route('two-factor.recovery-codes')
            ->with('status', 'recovery-codes-regenerated');
    }
}
