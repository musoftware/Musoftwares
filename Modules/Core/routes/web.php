<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    // Public routes (no auth)
    Route::get('/', [\Modules\Core\Http\Controllers\HomeController::class, 'index'])
        ->name('home');
});

Route::middleware(['web', 'auth'])->group(function () {
    // Authenticated routes
});

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin only routes
    });