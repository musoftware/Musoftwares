<?php

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\Api\AuthController;
use Modules\Tools\Http\Controllers\Api\LicenseController;
use Modules\Tools\Http\Controllers\Api\UpdateController;

/*
|--------------------------------------------------------------------------
| Tools Marketplace API Routes
|--------------------------------------------------------------------------
| These routes are called by Python desktop applications.
| All routes except /login return JSON.
|--------------------------------------------------------------------------
*/

Route::prefix('tools')->name('api.tools.')->group(function () {

    // ─── Desktop Authentication ─────────────────────────────────────────────
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    // ─── Sanctum-Protected Routes ───────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        // License Management
        Route::post('/license/activate', [LicenseController::class, 'activate'])->name('license.activate');
        Route::post('/license/check', [LicenseController::class, 'check'])->name('license.check');
        Route::post('/license/heartbeat', [LicenseController::class, 'heartbeat'])->name('license.heartbeat');

        // Update System
        Route::get('/{slug}/update-check', [UpdateController::class, 'check'])->name('update.check');
        Route::get('/{slug}/releases', [UpdateController::class, 'releases'])->name('releases');
    });
});
