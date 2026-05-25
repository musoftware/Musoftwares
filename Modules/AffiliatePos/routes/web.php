<?php

use Illuminate\Support\Facades\Route;
use Modules\AffiliatePos\Http\Controllers\AffiliatePosController;

Route::middleware(['auth', 'verified', 'feature:affiliate_pos'])->prefix('admin/affiliate-pos')->group(function () {
    Route::get('/pos', [\Modules\AffiliatePos\Http\Controllers\Web\PosController::class, 'index'])->name('affiliate_pos.pos.index');
});
