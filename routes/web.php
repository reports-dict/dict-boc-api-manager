<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SendController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransmissionController;
use App\Http\Middleware\JwtSessionAuth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(JwtSessionAuth::class)->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/send', [SendController::class, 'index'])->name('send.index');
    Route::post('/send', [SendController::class, 'store'])->name('send.store');
    Route::post('/send/preview', [SendController::class, 'preview'])->name('send.preview');

    Route::get('/transmissions', [TransmissionController::class, 'index'])->name('transmissions.index');
    Route::get('/transmissions/{transmission}', [TransmissionController::class, 'show'])->name('transmissions.show');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
