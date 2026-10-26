<?php

use Illuminate\Support\Facades\Route;
use Modules\PasswordSync\app\Http\Controllers\PasswordSyncController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/vault/sync', [PasswordSyncController::class, 'getVault']);
    Route::post('/vault/sync', [PasswordSyncController::class, 'updateVault']);
});
