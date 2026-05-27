<?php

use App\Http\Controllers\AmazonController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// ─── Guest routes ─────────────────────────────────────────────────────────────

Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// ─── Authenticated routes ─────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Orders
    Route::get('/orders',          [DashboardController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{orderId}', [OrderController::class, 'show'])->name('orders.show');

    // Amazon SP-API OAuth + management
    Route::prefix('amazon')->name('amazon.')->group(function () {
        Route::get('/',              [AmazonController::class, 'index'])->name('connect');
        Route::get('/redirect',      [AmazonController::class, 'redirect'])->name('redirect');
        Route::get('/callback',      [AmazonController::class, 'callback'])->name('callback');
        Route::delete('/disconnect', [AmazonController::class, 'disconnect'])->name('disconnect');
        Route::get('/sync',          [AmazonController::class, 'sync'])->name('sync');
    });
});


// TEMPORARY DEBUG ROUTE — remove after testing
Route::get('/debug/sync-order/{orderId}', function (string $orderId) {
    $account = auth()->user()->tenant->activeAmazonAccount();
    $service = app(\App\Services\Amazon\SpApiService::class);

    // Dump raw API response for order items
    $result = $service->debugOrderItems($account, $orderId);
    return response()->json($result);
})->middleware('auth');