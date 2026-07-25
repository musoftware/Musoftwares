<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsappSender\Http\Controllers\Api\WhatsappApiController;

// Standalone REST API Endpoint
Route::middleware(['auth:sanctum'])
    ->prefix('whatsapp')
    ->name('api.whatsapp.')
    ->group(function () {
        Route::post('/send', [WhatsappApiController::class, 'send'])->name('send');
    });
