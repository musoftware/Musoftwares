<?php

use Illuminate\Support\Facades\Route;
use Modules\Listing\Http\Controllers\ListingController;

// Public Routes (Accessible to all visitors, no auth/subscription required)
Route::get('/listing', [ListingController::class, 'index'])->name('listing.index');
Route::get('/listing/{id}', [ListingController::class, 'show'])->name('listing.show')->where('id', '[0-9]+');

// Private Routes (Require authentication, verified email, and listing subscription)
Route::prefix('listing')
    ->middleware(['auth', 'verified', 'subscription:listing'])
    ->name('listing.')
    ->group(function () {
        Route::get('/dashboard', [ListingController::class, 'dashboard'])->name('dashboard');
        Route::get('/{id}/edit', [ListingController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
        Route::put('/{id}', [ListingController::class, 'update'])->name('update')->where('id', '[0-9]+');
        Route::delete('/{id}', [ListingController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
    });
