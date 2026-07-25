<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsappSender\Http\Controllers\FacebookAuthController;
use Modules\WhatsappSender\Http\Controllers\WhatsappBusinessController;
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

        // Business Client Profiles & Wallet Recharge routes
        Route::post('/businesses', [WhatsappBusinessController::class, 'storeBusiness'])->name('businesses.store');
        Route::post('/businesses/{id}/recharge', [WhatsappBusinessController::class, 'rechargeWallet'])->name('businesses.recharge');
        Route::delete('/businesses/{id}', [WhatsappBusinessController::class, 'destroyBusiness'])->name('businesses.destroy');

        // Facebook Login OAuth routes
        Route::get('/auth/facebook', [FacebookAuthController::class, 'redirect'])->name('auth.facebook');
        Route::get('/auth/facebook/callback', [FacebookAuthController::class, 'callback'])->name('auth.facebook.callback');
    });
