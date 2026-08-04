<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsappSender\Http\Controllers\FacebookAuthController;
use Modules\WhatsappSender\Http\Controllers\WhatsappBusinessController;
use Modules\WhatsappSender\Http\Controllers\WhatsappSenderController;
use Modules\WhatsappSender\Http\Controllers\WhatsappTemplateController;
use Modules\WhatsappSender\Http\Controllers\WhatsappContactController;
use Modules\WhatsappSender\Http\Controllers\WhatsappScheduleController;
use Modules\WhatsappSender\Http\Controllers\GuestClientRegisterController;
use Modules\WhatsappSender\Http\Controllers\TelegramBotController;
use Modules\WhatsappSender\Http\Controllers\TelegramSubscriberController;
use Modules\WhatsappSender\Http\Controllers\BotFlowController;

// Fully independent standalone routes for WhatsApp Sender API system
Route::middleware(['auth'])
    ->prefix('whatsapp-sender')
    ->name('whatsapp.')
    ->group(function () {
        Route::get('/', [WhatsappSenderController::class, 'index'])->name('index');
        Route::get('/meta-app-guide', [WhatsappSenderController::class, 'showMetaAppGuide'])->name('meta-app-guide');
        Route::get('/businesses/{id}', [WhatsappSenderController::class, 'showBusinessWorkspace'])->name('businesses.workspace');
        Route::post('/accounts', [WhatsappSenderController::class, 'storeAccount'])->name('accounts.store');
        Route::match(['PUT', 'PATCH'], '/accounts/{id}', [WhatsappSenderController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{id}', [WhatsappSenderController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::post('/accounts/{id}/register', [WhatsappSenderController::class, 'registerAccount'])->name('accounts.register');
        Route::post('/accounts/{id}/sync', [WhatsappSenderController::class, 'syncAccountStatus'])->name('accounts.sync');
        Route::post('/accounts/{id}/test', [WhatsappSenderController::class, 'testAccount'])->name('accounts.test');
        Route::match(['PUT', 'PATCH'], '/accounts/{id}/waba', [WhatsappSenderController::class, 'updateWabaId'])->name('accounts.waba.update');
        Route::post('/send', [WhatsappSenderController::class, 'sendMessage'])->name('send');

        // Telegram Bots Management routes
        Route::post('/telegram-bots', [TelegramBotController::class, 'store'])->name('telegram-bots.store');
        Route::delete('/telegram-bots/{id}', [TelegramBotController::class, 'destroy'])->name('telegram-bots.destroy');

        // Business Client Profiles & Wallet Recharge routes
        Route::post('/businesses', [WhatsappBusinessController::class, 'storeBusiness'])->name('businesses.store');
        Route::put('/businesses/{id}', [WhatsappBusinessController::class, 'updateBusiness'])->name('businesses.update');
        Route::post('/businesses/{id}/recharge', [WhatsappBusinessController::class, 'rechargeWallet'])->name('businesses.recharge');
        Route::post('/businesses/{id}/webhook-token', [WhatsappBusinessController::class, 'updateWebhookToken'])->name('businesses.webhook-token');
        Route::post('/businesses/{id}/toggle-test-mode', [WhatsappBusinessController::class, 'toggleTestMode'])->name('businesses.toggle-test-mode');
        Route::delete('/businesses/{id}', [WhatsappBusinessController::class, 'destroyBusiness'])->name('businesses.destroy');

        // Facebook Login OAuth routes
        Route::get('/auth/facebook', [FacebookAuthController::class, 'redirect'])->name('auth.facebook');
        Route::get('/auth/facebook/callback', [FacebookAuthController::class, 'callback'])->name('auth.facebook.callback');

        // Webhook settings route
        Route::post('/webhook-settings', [WhatsappSenderController::class, 'updateWebhookSettings'])->name('webhook-settings.update');

        // Templates Management routes
        Route::post('/templates', [WhatsappTemplateController::class, 'store'])->name('templates.store');
        Route::delete('/templates/{id}', [WhatsappTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('/templates/{businessId}/sync', [WhatsappTemplateController::class, 'sync'])->name('templates.sync');

        // Contacts & Groups Management routes
        Route::post('/contact-groups', [WhatsappContactController::class, 'storeGroup'])->name('contact-groups.store');
        Route::delete('/contact-groups/{id}', [WhatsappContactController::class, 'destroyGroup'])->name('contact-groups.destroy');
        Route::post('/contact-groups/{groupId}/contacts', [WhatsappContactController::class, 'storeContacts'])->name('contacts.store');
        Route::delete('/contacts/{id}', [WhatsappContactController::class, 'destroyContact'])->name('contacts.destroy');

        // Scheduler & Campaigns routes
        Route::post('/schedules', [WhatsappScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('/schedules/{id}', [WhatsappScheduleController::class, 'destroy'])->name('schedules.destroy');
        Route::post('/send-campaign', [WhatsappSenderController::class, 'sendGroupCampaign'])->name('campaign.send');

        // Telegram Subscribers Management routes
        Route::post('/telegram-subscriber-groups', [TelegramSubscriberController::class, 'storeGroup'])->name('telegram.groups.store');
        Route::delete('/telegram-subscriber-groups/{id}', [TelegramSubscriberController::class, 'destroyGroup'])->name('telegram.groups.destroy');
        Route::post('/telegram-subscribers', [TelegramSubscriberController::class, 'storeSubscriber'])->name('telegram.subscribers.store');
        Route::put('/telegram-subscribers/{id}/group', [TelegramSubscriberController::class, 'updateSubscriberGroup'])->name('telegram.subscribers.update-group');
        Route::delete('/telegram-subscribers/{id}', [TelegramSubscriberController::class, 'destroySubscriber'])->name('telegram.subscribers.destroy');

        // Visual Bot Flows / Chatbots routes
        Route::post('/bot-flows', [BotFlowController::class, 'store'])->name('flows.store');
        Route::put('/bot-flows/{id}', [BotFlowController::class, 'update'])->name('flows.update');
        Route::post('/bot-flows/{id}/toggle', [BotFlowController::class, 'toggleActive'])->name('flows.toggle');
        Route::delete('/bot-flows/{id}', [BotFlowController::class, 'destroy'])->name('flows.destroy');
    });

// Public Guest Connect Routes (Does not require Auth middleware)
Route::name('whatsapp.guest.')->group(function () {
    Route::get('/whatsapp-sender/guest/connect/{uuid}', [GuestClientRegisterController::class, 'showLandingPage'])->name('register');
    Route::get('/whatsapp-sender/guest/connect/{uuid}/facebook', [GuestClientRegisterController::class, 'redirectFacebook'])->name('facebook.redirect');
    Route::get('/whatsapp-sender/guest/connect-callback', [GuestClientRegisterController::class, 'handleCallback'])->name('facebook.callback');
    Route::get('/whatsapp-sender/guest/connect/{uuid}/success', [GuestClientRegisterController::class, 'showSuccessPage'])->name('success');
});
