<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsappSender\Http\Controllers\Api\WhatsappApiController;

// Programmatic REST API Endpoints for WhatsApp Sender
Route::middleware(['auth:sanctum'])
    ->prefix('whatsapp')
    ->name('api.whatsapp.')
    ->group(function () {
        Route::post('/send', [WhatsappApiController::class, 'send'])->name('send');
        Route::get('/accounts', [WhatsappApiController::class, 'accounts'])->name('accounts');
        Route::get('/logs', [WhatsappApiController::class, 'logs'])->name('logs');
        Route::get('/logs/{id}', [WhatsappApiController::class, 'logDetails'])->name('log.details');
    });
