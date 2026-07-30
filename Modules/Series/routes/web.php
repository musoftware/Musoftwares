<?php

use Illuminate\Support\Facades\Route;
use Modules\Series\Http\Controllers\SeriesController;

Route::middleware(['web', 'auth', 'subscription:series'])
    ->prefix('series')
    ->name('series.')
    ->group(function () {
        Route::get('/', [SeriesController::class, 'index'])->name('index');
        Route::post('/sync', [SeriesController::class, 'sync'])->name('sync');
        Route::get('/playlist/{id}', [SeriesController::class, 'show'])->name('show')->where('id', '[0-9]+');
        Route::post('/video/{id}/notes', [SeriesController::class, 'saveNotes'])->name('video.notes')->where('id', '[0-9]+');
        Route::post('/video/{id}/complete', [SeriesController::class, 'toggleComplete'])->name('video.complete')->where('id', '[0-9]+');
    });
