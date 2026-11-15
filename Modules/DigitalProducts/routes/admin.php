<?php

use Illuminate\Support\Facades\Route;
use Modules\DigitalProducts\Http\Controllers\Admin\AdminDigitalCategoryController;
use Modules\DigitalProducts\Http\Controllers\Admin\AdminDigitalProductController;
use Modules\DigitalProducts\Http\Controllers\Admin\AdminDigitalProductStatsController;
use Modules\DigitalProducts\Http\Controllers\Admin\AdminDriveBookController;

Route::prefix('digital-products')->name('admin.digitalproducts.')->group(function () {
    // Books CRUD
    Route::get('/', [AdminDigitalProductController::class, 'index'])->name('index');
    Route::get('/create', [AdminDigitalProductController::class, 'create'])->name('create');
    Route::post('/', [AdminDigitalProductController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [AdminDigitalProductController::class, 'edit'])->name('edit');
    Route::get('/{id}/stats', [AdminDigitalProductStatsController::class, 'show'])->whereNumber('id')->name('stats');
    Route::get('/{id}/stats/export', [AdminDigitalProductStatsController::class, 'export'])->whereNumber('id')->name('stats.export');
    Route::put('/{id}', [AdminDigitalProductController::class, 'update'])->name('update');
    Route::delete('/{id}', [AdminDigitalProductController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-publish', [AdminDigitalProductController::class, 'togglePublish'])->name('toggle_publish');

    // A book brought from a Google Drive link instead of an upload
    Route::post('/drive', [AdminDriveBookController::class, 'fetch'])->middleware('throttle:20,1')->name('drive.fetch');
    Route::get('/drive/{token}', [AdminDriveBookController::class, 'show'])->name('drive.show');

    // Categories CRUD
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [AdminDigitalCategoryController::class, 'index'])->name('index');
        Route::post('/', [AdminDigitalCategoryController::class, 'store'])->name('store');
        Route::put('/{id}', [AdminDigitalCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminDigitalCategoryController::class, 'destroy'])->name('destroy');
    });
});
