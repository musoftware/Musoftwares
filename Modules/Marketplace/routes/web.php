<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        // Public browse
    });

Route::middleware(['web', 'auth'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        // Authenticated actions
    });