<?php

use Illuminate\Support\Facades\Route;
use Modules\Shortlink\Http\Controllers\ShortlinkController;
use Modules\Shortlink\Http\Controllers\ShortlinkRedirectController;

// Public — no auth. The short code is the access credential.
Route::get('/l/{code}', [ShortlinkRedirectController::class, 'redirect'])
    ->name('shortlink.redirect')
    ->where('code', '[A-Za-z0-9]{2,20}');

// Admin — matches the existing admin middleware convention (auth + role:admin).
Route::prefix('admin/shortlinks')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('admin.shortlinks.')
    ->group(function () {
        Route::get('/', [ShortlinkController::class, 'index'])->name('index');
        Route::post('/', [ShortlinkController::class, 'store'])->name('store');
        Route::delete('{shortlink}', [ShortlinkController::class, 'destroy'])->name('destroy');
        Route::post('{shortlink}/toggle', [ShortlinkController::class, 'toggle'])->name('toggle');
    });
