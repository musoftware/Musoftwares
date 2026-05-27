<?php

use Illuminate\Support\Facades\Route;
use Modules\GoldSavers\Http\Controllers\DashboardController;
use Modules\GoldSavers\Http\Controllers\WalletController;

Route::middleware(['auth', 'verified'])->prefix('isaas/gold-savers')->name('isaas.gold-savers.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    
    Route::prefix('wallets')->name('wallets.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/', [WalletController::class, 'store'])->name('store');
        Route::get('/{wallet}', [WalletController::class, 'show'])->name('show');
        Route::post('/{wallet}/transactions', [WalletController::class, 'addTransaction'])->name('transactions.store');
    });
});
