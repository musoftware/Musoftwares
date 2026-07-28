<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsappSender\Http\Controllers\Api\WhatsappApiController;

// Programmatic REST API Endpoints for WhatsApp Sender
Route::middleware(['auth:sanctum'])
    ->prefix('whatsapp')
    ->name('api.whatsapp.')
    ->group(function () {
        Route::post('/send', [WhatsappApiController::class, 'send'])->name('send');
        Route::post('/schedule', [WhatsappApiController::class, 'schedule'])->name('schedule');
        Route::get('/accounts', [WhatsappApiController::class, 'accounts'])->name('accounts');
        Route::get('/templates', [WhatsappApiController::class, 'templates'])->name('templates');
        Route::get('/groups', [WhatsappApiController::class, 'groups'])->name('groups');
        Route::get('/logs', [WhatsappApiController::class, 'logs'])->name('logs');
        Route::get('/logs/{id}', [WhatsappApiController::class, 'logDetails'])->name('log.details');
    });

// Public Telegram Webhook Endpoint
Route::post('/telegram/webhook/{bot_id}', [\Modules\WhatsappSender\Http\Controllers\TelegramWebhookController::class, 'handle'])
    ->name('api.telegram.webhook');
