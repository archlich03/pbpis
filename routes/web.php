<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BodyController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

// Users routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/profile', [UserController::class, 'updateProfile'])->name('users.updateProfile');
    Route::patch('/users/{user}/credentials', [UserController::class, 'updatePassword'])->name('users.updatePassword');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
    
    // IT admin/secretary 2FA and password management
    Route::delete('/users/{user}/two-factor', [UserController::class, 'removeTwoFactor'])->name('users.remove-two-factor');
    Route::post('/users/{user}/force-password-change', [UserController::class, 'forcePasswordChange'])->name('users.force-password-change');
    Route::patch('/users/{user}/cancel-password-change', [UserController::class, 'cancelPasswordChange'])->name('users.cancel-password-change');
});

// Bodies routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bodies', [BodyController::class, 'index'])->name('bodies.index');
    Route::get('/bodies/create', [BodyController::class, 'create'])->name('bodies.create');
    Route::post('/bodies', [BodyController::class, 'store'])->name('bodies.store');
    Route::get('/bodies/{body}', [BodyController::class, 'show'])->name('bodies.show');
    Route::get('/bodies/{body}/edit', [BodyController::class, 'edit'])->name('bodies.edit');
    Route::patch('/bodies/{body}', [BodyController::class, 'update'])->name('bodies.update');
    Route::delete('/bodies/{body}', [BodyController::class, 'destroy'])->name('bodies.destroy');
});

// Meetings routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bodies/{body}/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    Route::post('/bodies/{body}/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
    Route::patch('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
    Route::get('/meetings/{meeting}/protocolHTML', [MeetingController::class, 'protocol'])->name('meetings.protocol');
    Route::get('/meetings/{meeting}/protocolDOCX', [MeetingController::class, 'protocolDOCX'])->name('meetings.docx');
    Route::get('/meetings/{meeting}/protocolPDF', [MeetingController::class, 'protocolPDF'])->name('meetings.pdf');
    Route::get('/meetings/{meeting}/voting-report', [MeetingController::class, 'votingReport'])->name('meetings.voting-report');
});

// Attendance routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/meetings/{meeting}/attendance/toggle', [App\Http\Controllers\AttendanceController::class, 'toggle'])->name('attendance.toggle');
    Route::post('/meetings/{meeting}/attendance/mark-all', [App\Http\Controllers\AttendanceController::class, 'markAllPresent'])->name('attendance.mark-all');
    Route::post('/meetings/{meeting}/attendance/auto-mark', [App\Http\Controllers\AttendanceController::class, 'autoMarkFromVotes'])->name('attendance.auto-mark');
    Route::post('/meetings/{meeting}/attendance/mark-non-voters-absent', [App\Http\Controllers\AttendanceController::class, 'markNonVotersAbsent'])->name('attendance.mark-non-voters-absent');
});

// Questions routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/meetings/{meeting}/questions', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/meetings/{meeting}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('/meetings/{meeting}/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::patch('/meetings/{meeting}/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/meetings/{meeting}/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('/meetings/{meeting}/questions/reorder', [QuestionController::class, 'reorder'])->name('questions.reorder');
});

// Vote routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('/meetings/{meeting}/questions/{question}/votes', [VoteController::class, 'store'])->name('votes.store');
    Route::delete('/meetings/{meeting}/questions/{question}/votes', [VoteController::class, 'destroy'])->name('votes.destroy');
    
    // Proxy voting routes (for secretaries and IT admins)
    Route::put('/meetings/{meeting}/questions/{question}/proxy-votes', [VoteController::class, 'storeProxy'])->name('votes.proxy');
    Route::delete('/meetings/{meeting}/questions/{question}/proxy-votes', [VoteController::class, 'destroyProxy'])->name('votes.proxy-destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // User history route
    Route::get('/history', [UserController::class, 'history'])->name('user.history');
    
    // Admin audit logs route (only for IT administrators and secretaries)
    Route::get('/audit-logs', [UserController::class, 'auditLogs'])->name('audit.logs');
});


// Theme routes
Route::post('/theme/toggle', [ThemeController::class, 'toggle'])->name('theme.toggle');
Route::post('/theme/set', [ThemeController::class, 'set'])->name('theme.set');

Route::get('/locale', function (Request $request) {
    $locale = $request->query('locale');

    if ($locale && in_array($locale, array_keys(config('app.available_locales')))) {
        Session::put('locale', $locale);
        App::setLocale($locale);
    }

    return redirect()->back();
})->name('locale.change');

// 2FA routes

Route::middleware(['auth'])->group(function () {
    // 2FA setup routes
    Route::get('/two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
    Route::get('/two-factor/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])->name('two-factor.recovery-codes');
    Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes.regenerate');
});

// 2FA verification routes (no auth middleware)
Route::get('/two-factor/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
Route::post('/two-factor/verify', [TwoFactorController::class, 'verifyCode'])->name('two-factor.verify.post');

// Force password change route
Route::middleware(['auth'])->group(function () {
    Route::get('/force-password-change', function () {
        $user = auth()->user();
        if (!$user->password_change_required || !empty($user->ms_id)) {
            return redirect()->route('dashboard');
        }
        return view('auth.force-password-change');
    })->name('force-password-change');
});

require __DIR__.'/auth.php';