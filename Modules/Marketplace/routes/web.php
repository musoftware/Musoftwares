<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\DashboardController;
use Modules\Marketplace\Http\Controllers\ServiceController;

// Single group — order matters: literal routes BEFORE wildcards
Route::middleware('web')
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {

        // ── Public ────────────────────────────────────────────────────────
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

        // ── Auth-only ─────────────────────────────────────────────────────
        Route::middleware('auth')->group(function () {

            // Dashboard
            Route::get('/',         [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard',[DashboardController::class, 'index'])->name('dashboard.alias');

            // !! MUST be before /services/{id} !!
            Route::get('/services/create',  [ServiceController::class, 'create'])->name('services.create');
            Route::post('/services',        [ServiceController::class, 'store'])->name('services.store');

            // Admin actions
            Route::post('/services/{id}/approve', [ServiceController::class, 'approve'])->name('services.approve');
            Route::post('/services/{id}/reject',  [ServiceController::class, 'reject'])->name('services.reject');
            Route::post('/services/{id}/feature', [ServiceController::class, 'feature'])->name('services.feature');
        });

        // ── Wildcard — always last ─────────────────────────────────────────
        Route::get('/services/{id}', [ServiceController::class, 'show'])->name('services.show');
    });