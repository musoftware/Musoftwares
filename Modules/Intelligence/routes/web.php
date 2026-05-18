<?php

use Illuminate\Support\Facades\Route;
use Modules\Intelligence\Http\Controllers\DashboardController;
use Modules\Intelligence\Http\Controllers\CompetitorController;
use Modules\Intelligence\Http\Controllers\AdFeedController;
use Modules\Intelligence\Http\Controllers\UgcController;
use Modules\Intelligence\Http\Controllers\SwipeVaultController;

Route::middleware(['web', 'auth'])->prefix('intelligence')->name('intelligence.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    
    Route::get('/competitors', [CompetitorController::class, 'index'])->name('competitors.index');
    Route::get('/competitors/{competitor}', [CompetitorController::class, 'show'])->name('competitors.show');
    
    Route::get('/ads', [AdFeedController::class, 'index'])->name('ads.index');
    
    Route::get('/ugc', [UgcController::class, 'index'])->name('ugc.index');
    
    Route::get('/swipe-vault', [SwipeVaultController::class, 'index'])->name('swipe.index');
});
