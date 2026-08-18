<?php

use Illuminate\Support\Facades\Route;
use Modules\DigitalProducts\Http\Controllers\Public\FreeDownloadController;
use Modules\DigitalProducts\Http\Controllers\Public\LibraryController;
use Modules\DigitalProducts\Http\Controllers\Public\LibraryPurchaseController;
use Modules\DigitalProducts\Http\Controllers\Public\MyLibraryController;

Route::prefix('library')->name('library.')->group(function () {
    // Public Storefront Catalog & Details
    Route::get('/', [LibraryController::class, 'index'])->name('index');
    Route::get('/book/{slug}', [LibraryController::class, 'show'])->name('show');

    // Free Download Link Request & Token Verification
    Route::post('/book/{slug}/free-download', [FreeDownloadController::class, 'requestLink'])->name('free_download');
    Route::get('/download/{token}', [FreeDownloadController::class, 'downloadByToken'])->name('download.token');

    // Authenticated User Routes (My Library & Purchase)
    Route::middleware('auth')->group(function () {
        Route::get('/my-library', [MyLibraryController::class, 'index'])->name('my_library');
        Route::get('/my-library/download/{slug}', [MyLibraryController::class, 'download'])->name('my_library.download');
        Route::post('/book/{slug}/buy-wallet', [LibraryPurchaseController::class, 'purchaseWithWallet'])->name('buy.wallet');
    });
});
