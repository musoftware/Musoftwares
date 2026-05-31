<?php

use Illuminate\Support\Facades\Route;
use Modules\SmsPaymentGateway\Http\Controllers\SmsPaymentGatewayController;
use Modules\SmsPaymentGateway\Http\Controllers\WidgetController;

// Public Widget Routes
Route::middleware(['web'])->prefix('sms-payment-gateway')->name('sms-payment-gateway.')->group(function () {
    Route::get('pay', [WidgetController::class, 'show'])->name('widget.show');
    Route::get('pay/status', [WidgetController::class, 'status'])->name('widget.status');
});

Route::middleware(['web', 'auth', 'verified', 'onboarding', 'subscription:sms-payment-gateway'])->prefix('sms-payment-gateway')->name('sms-payment-gateway.')->group(function () {
    Route::get('/', [SmsPaymentGatewayController::class, 'index'])->name('index');
    Route::get('devices', [SmsPaymentGatewayController::class, 'devices'])->name('devices');
    Route::get('webhooks', [SmsPaymentGatewayController::class, 'webhooks'])->name('webhooks');
    Route::get('verification', [SmsPaymentGatewayController::class, 'verification'])->name('verification');
    Route::get('documentation', [SmsPaymentGatewayController::class, 'documentation'])->name('documentation');
    Route::get('integration-tester', [SmsPaymentGatewayController::class, 'integrationTester'])->name('integration-tester');
    Route::post('integration-tester/run', [SmsPaymentGatewayController::class, 'runIntegrationTest'])->name('integration-tester.run');
    Route::get('verification-secret-page', [SmsPaymentGatewayController::class, 'verificationSecretPage'])->name('verification-secret-page');
    Route::post('generate-qr', [SmsPaymentGatewayController::class, 'generateQrCode'])->name('generate-qr');
    Route::get('device/{id}', [SmsPaymentGatewayController::class, 'showDevice'])->name('device');
    Route::delete('device/{id}', [SmsPaymentGatewayController::class, 'deleteDevice'])->name('delete-device');
    Route::delete('device/{id}/transactions', [SmsPaymentGatewayController::class, 'clearTransactions'])->name('clear-transactions');
    Route::patch('device/{id}/toggle-spoof-detection', [SmsPaymentGatewayController::class, 'toggleSpoofDetection'])->name('toggle-spoof-detection');
    Route::get('device/{deviceId}/transactions', [SmsPaymentGatewayController::class, 'getTransactions'])->name('transactions');
    Route::get('verification-secret', [SmsPaymentGatewayController::class, 'getVerificationSecret'])->name('verification-secret');
    Route::post('verification-secret/regenerate', [SmsPaymentGatewayController::class, 'regenerateVerificationSecret'])->name('verification-secret.regenerate');
    Route::post('webhook', [SmsPaymentGatewayController::class, 'updateWebhook'])->name('webhook.update');
    Route::delete('webhook/{id}', [SmsPaymentGatewayController::class, 'deleteWebhook'])->name('webhook.delete');
    Route::post('webhook/test', [SmsPaymentGatewayController::class, 'testWebhook'])->name('webhook.test');
    Route::get('wallets', [SmsPaymentGatewayController::class, 'wallets'])->name('wallets');
    Route::post('wallets', [SmsPaymentGatewayController::class, 'storeWallet'])->name('wallets.store');
    Route::delete('wallets/{id}', [SmsPaymentGatewayController::class, 'deleteWallet'])->name('wallets.delete');
    Route::get('test-mode', [SmsPaymentGatewayController::class, 'testMode'])->name('test-mode');
    Route::post('test-mode/toggle', [SmsPaymentGatewayController::class, 'toggleTestMode'])->name('test-mode.toggle');
    Route::post('test-mode/create-transaction', [SmsPaymentGatewayController::class, 'createTestTransaction'])->name('test-mode.create-transaction');
    Route::post('test-mode/send-webhook', [SmsPaymentGatewayController::class, 'sendTestWebhook'])->name('test-mode.send-webhook');
    Route::delete('test-mode/clear-data', [SmsPaymentGatewayController::class, 'clearTestData'])->name('test-mode.clear-data');
});

