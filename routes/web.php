<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BodyController;
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
    Route::get('/bodies/{body}/edit', [BodyController::class, 'edit'])->name('bodies.edit');
    Route::patch('/bodies/{body}', [BodyController::class, 'update'])->name('bodies.update');
    Route::delete('/bodies/{body}', [BodyController::class, 'destroy'])->name('bodies.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';