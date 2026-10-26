<?php

use Illuminate\Support\Facades\Route;
use Modules\PasswordSync\app\Http\Controllers\PasswordSyncController;

Route::middleware('web')->group(function () {
    Route::get('/extension/auth/success', [PasswordSyncController::class, 'authSuccess'])->name('passwordsync.auth.success');
});
