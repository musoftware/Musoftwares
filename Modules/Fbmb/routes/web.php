<?php

use Illuminate\Support\Facades\Route;
use Modules\Fbmb\Http\Controllers\FbmbLookupController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('fbmb', [FbmbLookupController::class, 'index'])->name('fbmb.index');
    Route::post('fbmb/process', [FbmbLookupController::class, 'process'])->name('fbmb.process');
    Route::get('fbmb/download', [FbmbLookupController::class, 'download'])->name('fbmb.download');
});
