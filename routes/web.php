<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BodyController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.panel');
    Route::patch('/users/{user}/profile', [UserController::class, 'updateProfile'])->name('users.updateProfile');
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.updatePassword');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bodies', [BodyController::class, 'index'])->name('bodies.panel');
    Route::get('/bodies/create', [BodyController::class, 'create'])->name('bodies.create');
    Route::post('/bodies', [BodyController::class, 'store'])->name('bodies.store');
    Route::get('/bodies/{body}', [BodyController::class, 'show'])->name('bodies.show');
    Route::get('/bodies/{body}/edit', [BodyController::class, 'edit'])->name('bodies.edit');
    Route::patch('/bodies/{body}', [BodyController::class, 'update'])->name('bodies.update');
    Route::delete('/bodies/{body}', [BodyController::class, 'destroy'])->name('bodies.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bodies/{body}/meeting', [MeetingController::class, 'create'])->name('meetings.create');
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.panel');
    Route::post('/meetings/{body}', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
    Route::patch('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/meetings/{meeting}/questions', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/meetings/{meeting}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('/meetings/{meeting}/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::patch('/meetings/{meeting}/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/meetings/{meeting}/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::get('/meetings/{meeting}/{question}', function($meeting, $question){
        return redirect()->route('meetings.show', $meeting);
    })->name('questions.redirect');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';