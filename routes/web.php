<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BodyController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\MeetingController;
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
    Route::get('/meetings/{meeting}/protocolPDF', [MeetingController::class, 'protocolPDF'])->name('meetings.pdf');
});

// Questions routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/meetings/{meeting}/questions', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/meetings/{meeting}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('/meetings/{meeting}/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::patch('/meetings/{meeting}/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/meetings/{meeting}/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
});

// Vote routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('/meetings/{meeting}/questions/{question}/votes', [VoteController::class, 'store'])->name('votes.store');
    Route::delete('/meetings/{meeting}/questions/{question}/votes', [VoteController::class, 'destroy'])->name('votes.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/locale', function (Request $request) {
    $locale = $request->query('locale');

    if ($locale && in_array($locale, array_keys(config('app.available_locales')))) {
        Session::put('locale', $locale);
        App::setLocale($locale);
    }

    return redirect()->back();
})->name('locale.change');

require __DIR__.'/auth.php';