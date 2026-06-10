<?php

use Illuminate\Support\Facades\Route;
use Modules\PasswordSync\Http\Controllers\PasswordSyncController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('passwordsyncs', PasswordSyncController::class)->names('passwordsync');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/vault/sync', [PasswordSyncController::class, 'getVault']);
    Route::post('/vault/sync', [PasswordSyncController::class, 'updateVault']);
});
