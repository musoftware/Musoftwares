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

// -- Seller Landing Pages ------------------------------------------
Route::middleware(['web', 'auth'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        Route::get('/landing-pages', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'index'])->name('landing-pages.index');
        Route::get('/landing-pages/create/{service}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'create'])->name('landing-pages.create');
        Route::post('/landing-pages/{service}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'store'])->name('landing-pages.store');
        Route::get('/landing-pages/{service}/edit/{landingPage?}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'edit'])->name('landing-pages.edit');
        Route::put('/landing-pages/{service}/{landingPage?}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'update'])->name('landing-pages.update');
        Route::get('/landing-pages/{service}/submissions', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'submissions'])->name('landing-pages.submissions');
        Route::get('/landing-pages/{service}/analytics', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'analytics'])->name('landing-pages.analytics');
    });

// -- Admin Routes --------------------------------------------------
Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/marketplace')
    ->name('admin.marketplace.')
    ->group(function () {
        Route::get('/service-landing-pages', [\Modules\Marketplace\Http\Controllers\Admin\AdminServiceLandingPageController::class, 'index'])->name('service-landing-pages.index');
        Route::post('/service-landing-pages/{landingPage}/toggle-status', [\Modules\Marketplace\Http\Controllers\Admin\AdminServiceLandingPageController::class, 'toggleStatus'])->name('service-landing-pages.toggle-status');
        Route::delete('/service-landing-pages/{landingPage}', [\Modules\Marketplace\Http\Controllers\Admin\AdminServiceLandingPageController::class, 'destroy'])->name('service-landing-pages.destroy');
    });
