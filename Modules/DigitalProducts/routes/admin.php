<?php

use Illuminate\Support\Facades\Route;
use Modules\DigitalProducts\Http\Controllers\Admin\AdminDigitalCategoryController;
use Modules\DigitalProducts\Http\Controllers\Admin\AdminDigitalProductController;

Route::prefix('digital-products')->name('admin.digitalproducts.')->group(function () {
    // Books CRUD
    Route::get('/', [AdminDigitalProductController::class, 'index'])->name('index');
    Route::get('/create', [AdminDigitalProductController::class, 'create'])->name('create');
    Route::post('/', [AdminDigitalProductController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [AdminDigitalProductController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AdminDigitalProductController::class, 'update'])->name('update');
    Route::delete('/{id}', [AdminDigitalProductController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-publish', [AdminDigitalProductController::class, 'togglePublish'])->name('toggle_publish');

    // Categories CRUD
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [AdminDigitalCategoryController::class, 'index'])->name('index');
        Route::post('/', [AdminDigitalCategoryController::class, 'store'])->name('store');
        Route::put('/{id}', [AdminDigitalCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminDigitalCategoryController::class, 'destroy'])->name('destroy');
    });
});
