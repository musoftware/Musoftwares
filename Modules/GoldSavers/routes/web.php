<?php

use Illuminate\Support\Facades\Route;
use Modules\GoldSavers\Http\Controllers\DashboardController;
use Modules\GoldSavers\Http\Controllers\WalletController;
use Modules\GoldSavers\Http\Controllers\MarketController;
use Modules\GoldSavers\Http\Controllers\AnalyticsController;
use Modules\GoldSavers\Http\Controllers\ReportsController;

Route::middleware(['auth', 'verified'])->prefix('isaas/gold-savers')->name('isaas.gold-savers.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/live-prices', [DashboardController::class, 'livePrices'])->name('live-prices');
    Route::post('/live-prices/refresh', [DashboardController::class, 'refreshPrices'])->name('live-prices.refresh');
    
    Route::get('/market', [MarketController::class, 'index'])->name('market.index');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    
    // Reports
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/download', [ReportsController::class, 'downloadPdf'])->name('reports.download');
    
    Route::prefix('wallets')->name('wallets.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/', [WalletController::class, 'store'])->name('store');
        Route::get('/{wallet}', [WalletController::class, 'show'])->name('show');
        Route::put('/{wallet}', [WalletController::class, 'update'])->name('update');
        Route::delete('/{wallet}', [WalletController::class, 'destroy'])->name('destroy');
        Route::post('/{wallet}/transactions', [WalletController::class, 'addTransaction'])->name('transactions.store');
        Route::put('/{wallet}/transactions/{transaction}', [WalletController::class, 'updateTransaction'])->name('transactions.update');
        Route::delete('/{wallet}/transactions/{transaction}', [WalletController::class, 'destroyTransaction'])->name('transactions.destroy');
    });
});
