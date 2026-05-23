<?php

use Illuminate\Support\Facades\Route;
use Modules\GoldSavers\Http\Controllers\GoldSaversController;

Route::middleware(['auth', 'verified'])->prefix('isaas/gold-savers')->name('isaas.gold-savers.')->group(function () {
    Route::get('/', [GoldSaversController::class, 'index'])->name('index');
    Route::post('/', [GoldSaversController::class, 'store'])->name('store');
    Route::delete('/{goldSaver}', [GoldSaversController::class, 'destroy'])->name('destroy');
});
