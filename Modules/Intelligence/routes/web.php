<?php

use Illuminate\Support\Facades\Route;
use Modules\Intelligence\Http\Controllers\DashboardController;
use Modules\Intelligence\Http\Controllers\CompetitorController;
use Modules\Intelligence\Http\Controllers\AdFeedController;
use Modules\Intelligence\Http\Controllers\UgcController;
use Modules\Intelligence\Http\Controllers\SwipeVaultController;
// use Modules\Intelligence\Http\Controllers\ISaasController; // removed - replaced by fbmb module

Route::middleware(['web', 'auth', 'verified', 'onboarding', 'subscription:intelligence'])->prefix('intelligence')->name('intelligence.')->group(function () {

    // ── Dashboard ──────────────────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    // ── Competitors ────────────────────────────────────────────────────────
    Route::get('/competitors', [CompetitorController::class, 'index'])->name('competitors.index');
    Route::post('/competitors', [CompetitorController::class, 'store'])->name('competitors.store');
    Route::get('/competitors/{competitor}', [CompetitorController::class, 'show'])->name('competitors.show');
    Route::put('/competitors/{competitor}', [CompetitorController::class, 'update'])->name('competitors.update');
    Route::delete('/competitors/{competitor}', [CompetitorController::class, 'destroy'])->name('competitors.destroy');

    // ── Ad Feed ────────────────────────────────────────────────────────────
    Route::get('/ads', [AdFeedController::class, 'index'])->name('ads.index');
    Route::post('/ads', [AdFeedController::class, 'store'])->name('ads.store');
    Route::delete('/ads/{ad}', [AdFeedController::class, 'destroy'])->name('ads.destroy');

    // ── UGC Creators ───────────────────────────────────────────────────────
    Route::get('/ugc', [UgcController::class, 'index'])->name('ugc.index');
    Route::post('/ugc', [UgcController::class, 'store'])->name('ugc.store');
    Route::put('/ugc/{creator}', [UgcController::class, 'update'])->name('ugc.update');
    Route::delete('/ugc/{creator}', [UgcController::class, 'destroy'])->name('ugc.destroy');

    // ── Swipe Vault ────────────────────────────────────────────────────────
    Route::get('/swipe-vault', [SwipeVaultController::class, 'index'])->name('swipe.index');
    Route::post('/swipe-vault/collections', [SwipeVaultController::class, 'storeCollection'])->name('swipe.collections.store');
    Route::delete('/swipe-vault/collections/{collection}', [SwipeVaultController::class, 'destroyCollection'])->name('swipe.collections.destroy');
    Route::post('/swipe-vault/collections/{collection}/items', [SwipeVaultController::class, 'storeItem'])->name('swipe.items.store');
    Route::delete('/swipe-vault/collections/{collection}/items/{item}', [SwipeVaultController::class, 'destroyItem'])->name('swipe.items.destroy');

    // ── iSAAS Lookup (removed) ─────────────────────────────────────────────
    // Route::get('/fbmb', [\Modules\fbmb\Http\Controllers\FbmbLookupController::class, 'index'])->name('fbmb.index');
    // Route::post('/fbmb/process', [\Modules\fbmb\Http\Controllers\FbmbLookupController::class, 'process'])->name('fbmb.process'); // old iSAAS routes removed
});

