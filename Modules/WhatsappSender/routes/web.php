<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsappSender\Http\Controllers\FacebookAuthController;
use Modules\WhatsappSender\Http\Controllers\WhatsappSenderController;

// Fully independent standalone routes for WhatsApp Sender API system
Route::middleware(['auth'])
    ->prefix('whatsapp-sender')
    ->name('whatsapp.')
    ->group(function () {
        Route::get('/', [WhatsappSenderController::class, 'index'])->name('index');
        Route::post('/accounts', [WhatsappSenderController::class, 'storeAccount'])->name('accounts.store');
        Route::delete('/accounts/{id}', [WhatsappSenderController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::post('/send', [WhatsappSenderController::class, 'sendMessage'])->name('send');

        // Facebook Login OAuth routes
        Route::get('/auth/facebook', [FacebookAuthController::class, 'redirect'])->name('auth.facebook');
        Route::get('/auth/facebook/callback', [FacebookAuthController::class, 'callback'])->name('auth.facebook.callback');
    });
