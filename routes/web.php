<?php

use App\Http\Controllers\AmazonController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ─── Guest routes ─────────────────────────────────────────────────────────────

Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// ─── Authenticated routes ─────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {

    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Orders
    Route::get('/orders', [DashboardController::class, 'orders'])->name('orders.index');

    // Amazon SP-API OAuth + management
    Route::prefix('amazon')->name('amazon.')->group(function () {
        Route::get('/',           [AmazonController::class, 'index'])->name('connect');
        Route::get('/redirect',   [AmazonController::class, 'redirect'])->name('redirect');
        Route::get('/callback',   [AmazonController::class, 'callback'])->name('callback');
        Route::delete('/disconnect', [AmazonController::class, 'disconnect'])->name('disconnect');
        Route::get('/sync',       [AmazonController::class, 'sync'])->name('sync');
    });
});
