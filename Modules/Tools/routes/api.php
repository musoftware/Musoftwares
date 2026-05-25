<?php

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\Api\AgentPluginController;
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

        // ─── Agent Plugin Sync ──────────────────────────────────────────────
        // Polled by local agents to get list of subscribed plugins to auto-download.
        // Free tools (is_free=true) are automatically included for every authenticated user.
        Route::get('/agent/plugins', [AgentPluginController::class, 'index'])->name('agent.plugins');

    });

    // Signed plugin download (called by agent syncer)
    Route::get('/agent/plugins/{slug}/download', [AgentPluginController::class, 'download'])->name('plugin.download');
});
