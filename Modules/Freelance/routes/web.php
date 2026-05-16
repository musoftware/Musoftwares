<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('freelance')
    ->name('freelance.')
    ->group(function () {
        // All clients can access
    });