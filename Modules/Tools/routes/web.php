<?php

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\DownloadController;
use Modules\Tools\Http\Controllers\LicenseController;
use Modules\Tools\Http\Controllers\MarketplaceController;
use Modules\Tools\Http\Controllers\SubscriptionController;

// ─── Public Marketplace (auth optional for browsing) ───────────────────────────
Route::prefix('tools')->name('tools.')->group(function () {

    // Browse
    Route::get('/', [MarketplaceController::class, 'index'])->name('explore');
    Route::get('/{slug}', [MarketplaceController::class, 'show'])->name('show');

    // Auth-required routes
    Route::middleware('auth')->group(function () {

        // Subscriptions & Billing
        Route::get('/billing/overview', [SubscriptionController::class, 'billing'])->name('billing');
        Route::get('/{slug}/subscribe/{planId}', [SubscriptionController::class, 'checkout'])->name('checkout');
        Route::post('/{slug}/subscribe/{planId}', [SubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::post('/subscriptions/{id}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

        // Downloads
        Route::get('/my/downloads', [DownloadController::class, 'index'])->name('downloads');
        Route::get('/{slug}/download', [DownloadController::class, 'generate'])->name('download.generate');
        Route::get('/{slug}/download/{version_id}/serve', [DownloadController::class, 'serve'])->name('download.serve');

        // Licenses
        Route::get('/my/licenses', [LicenseController::class, 'index'])->name('my-licenses');
        Route::get('/my/licenses/{licenseId}/devices', [LicenseController::class, 'devices'])->name('devices');
        Route::delete('/my/licenses/{licenseId}/devices/{deviceId}', [LicenseController::class, 'revokeDevice'])->name('devices.revoke');
    });
});
