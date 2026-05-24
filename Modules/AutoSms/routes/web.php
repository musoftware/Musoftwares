<?php

use Illuminate\Support\Facades\Route;
use Modules\AutoSms\Http\Controllers\AutoSmsController;

Route::middleware(['web', 'auth', 'verified', 'onboarding', 'subscription:intelligence'])->prefix('auto-sms')->name('autosms.')->group(function () {
    Route::get('/', [AutoSmsController::class, 'index'])->name('index');
    Route::get('devices', [AutoSmsController::class, 'devices'])->name('devices');
    Route::get('webhooks', [AutoSmsController::class, 'webhooks'])->name('webhooks');
    Route::get('verification', [AutoSmsController::class, 'verification'])->name('verification');
    Route::get('documentation', [AutoSmsController::class, 'documentation'])->name('documentation');
    Route::get('integration-tester', [AutoSmsController::class, 'integrationTester'])->name('integration-tester');
    Route::post('integration-tester/run', [AutoSmsController::class, 'runIntegrationTest'])->name('integration-tester.run');
    Route::get('verification-secret-page', [AutoSmsController::class, 'verificationSecretPage'])->name('verification-secret-page');
    Route::post('generate-qr', [AutoSmsController::class, 'generateQrCode'])->name('generate-qr');
    Route::get('device/{id}', [AutoSmsController::class, 'showDevice'])->name('device');
    Route::delete('device/{id}', [AutoSmsController::class, 'deleteDevice'])->name('delete-device');
    Route::delete('device/{id}/transactions', [AutoSmsController::class, 'clearTransactions'])->name('clear-transactions');
    Route::patch('device/{id}/toggle-spoof-detection', [AutoSmsController::class, 'toggleSpoofDetection'])->name('toggle-spoof-detection');
    Route::get('device/{deviceId}/transactions', [AutoSmsController::class, 'getTransactions'])->name('transactions');
    Route::get('verification-secret', [AutoSmsController::class, 'getVerificationSecret'])->name('verification-secret');
    Route::post('verification-secret/regenerate', [AutoSmsController::class, 'regenerateVerificationSecret'])->name('verification-secret.regenerate');
    Route::post('webhook', [AutoSmsController::class, 'updateWebhook'])->name('webhook.update');
    Route::delete('webhook/{id}', [AutoSmsController::class, 'deleteWebhook'])->name('webhook.delete');
    Route::post('webhook/test', [AutoSmsController::class, 'testWebhook'])->name('webhook.test');
    Route::get('wallets', [AutoSmsController::class, 'wallets'])->name('wallets');
    Route::post('wallets', [AutoSmsController::class, 'storeWallet'])->name('wallets.store');
    Route::delete('wallets/{id}', [AutoSmsController::class, 'deleteWallet'])->name('wallets.delete');
    Route::get('test-mode', [AutoSmsController::class, 'testMode'])->name('test-mode');
    Route::post('test-mode/toggle', [AutoSmsController::class, 'toggleTestMode'])->name('test-mode.toggle');
    Route::post('test-mode/create-transaction', [AutoSmsController::class, 'createTestTransaction'])->name('test-mode.create-transaction');
    Route::post('test-mode/send-webhook', [AutoSmsController::class, 'sendTestWebhook'])->name('test-mode.send-webhook');
    Route::delete('test-mode/clear-data', [AutoSmsController::class, 'clearTestData'])->name('test-mode.clear-data');
});

