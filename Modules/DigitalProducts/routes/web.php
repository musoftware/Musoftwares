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
    // The download is asked for by the form on the book page; opening the address itself goes to that page.
    Route::get('/book/{slug}/free-download', fn (string $slug) => redirect()->route('library.show', $slug));
    Route::get('/download/{token}', [FreeDownloadController::class, 'downloadByToken'])->name('download.token');

    // Authenticated User Routes (My Library & Purchase)
    Route::middleware('auth')->group(function () {
        Route::get('/my-library', [MyLibraryController::class, 'index'])->name('my_library');
        Route::get('/my-library/download/{slug}', [MyLibraryController::class, 'download'])->name('my_library.download');
        Route::post('/book/{slug}/buy-wallet', [LibraryPurchaseController::class, 'purchaseWithWallet'])->name('buy.wallet');
    });

    // The admin once linked books as /library/{slug}; those shared links still open the book.
    Route::get('/{slug}', fn (string $slug) => redirect()->route('library.show', $slug, 301))
        ->where('slug', '[A-Za-z0-9\-]+')->name('old_book_link');
});
