<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
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

// ─── Authenticated routes (seller / tenant area) ──────────────────────────────

Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Orders
    Route::get('/orders',          [DashboardController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{orderId}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{orderId}/items/{itemId}/customization', [OrderController::class, 'saveCustomization'])->name('orders.customization.save');

    // Amazon SP-API OAuth + management
    Route::prefix('amazon')->name('amazon.')->group(function () {
        Route::get('/',              [AmazonController::class, 'index'])->name('connect');
        Route::get('/redirect',      [AmazonController::class, 'redirect'])->name('redirect');
        Route::get('/callback',      [AmazonController::class, 'callback'])->name('callback');
        Route::delete('/disconnect', [AmazonController::class, 'disconnect'])->name('disconnect');
        Route::get('/sync',          [AmazonController::class, 'sync'])->name('sync');
    });
});

// ─── Platform admin routes (super admins only) ────────────────────────────────

Route::middleware(['auth', 'super_admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users — full CRUD across all tenants
    Route::get('/users',                    [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create',             [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users',                   [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit',        [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}',             [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}',          [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/toggle',    [AdminUserController::class, 'toggleActive'])->name('users.toggle');

    // Tenants — lightweight management
    Route::get('/tenants',          [AdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create',   [AdminTenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants',         [AdminTenantController::class, 'store'])->name('tenants.store');

    // Orders — read-only, across every tenant
    Route::get('/orders',           [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{orderId}', [AdminOrderController::class, 'show'])->name('orders.show');
});


// TEMPORARY DEBUG ROUTE — remove after testing
Route::get('/debug/sync-order/{orderId}', function (string $orderId) {
    $account = auth()->user()->tenant->activeAmazonAccount();
    $service = app(\App\Services\Amazon\SpApiService::class);

    // Dump raw API response for order items
    $result = $service->debugOrderItems($account, $orderId);
    return response()->json($result);
})->middleware('auth');