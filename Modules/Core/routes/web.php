<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    // Public routes (no auth)
    Route::get('/', [\Modules\Core\Http\Controllers\HomeController::class, 'index'])
        ->name('home');
});

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Points System
    Route::get('/points', [\Modules\Core\Http\Controllers\PointsDashboardController::class, 'index'])->name('points.index');
    Route::resource('point-packages', \Modules\Core\Http\Controllers\PointPackageController::class)->except(['create', 'show', 'edit']);
    Route::post('/point-purchases', [\Modules\Core\Http\Controllers\PointPurchaseController::class, 'store'])->name('point-purchases.store');
    Route::post('/point-purchases/wallet', [\Modules\Core\Http\Controllers\PointPurchaseController::class, 'storeWallet'])->name('point-purchases.store-wallet');
    Route::get('/point-purchases/success', [\Modules\Core\Http\Controllers\PointPurchaseController::class, 'success'])->name('point-purchases.success');
    Route::get('/point-purchases/failure', [\Modules\Core\Http\Controllers\PointPurchaseController::class, 'failure'])->name('point-purchases.failure');
});

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin only routes
    });