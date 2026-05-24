<?php

use Illuminate\Support\Facades\Route;
use Modules\TextPaymentGateway\Http\Controllers\TextPaymentGatewayController;
use Modules\TextPaymentGateway\Http\Controllers\WidgetController;

// Public Widget Routes
Route::middleware(['web'])->prefix('text-payment-gateway')->name('text-payment-gateway.')->group(function () {
    Route::get('pay', [WidgetController::class, 'show'])->name('widget.show');
    Route::get('pay/status', [WidgetController::class, 'status'])->name('widget.status');
});

Route::middleware(['web', 'auth', 'verified', 'onboarding', 'subscription:intelligence'])->prefix('text-payment-gateway')->name('text-payment-gateway.')->group(function () {
    Route::get('/', [TextPaymentGatewayController::class, 'index'])->name('index');
    Route::get('devices', [TextPaymentGatewayController::class, 'devices'])->name('devices');
    Route::get('webhooks', [TextPaymentGatewayController::class, 'webhooks'])->name('webhooks');
    Route::get('verification', [TextPaymentGatewayController::class, 'verification'])->name('verification');
    Route::get('documentation', [TextPaymentGatewayController::class, 'documentation'])->name('documentation');
    Route::get('integration-tester', [TextPaymentGatewayController::class, 'integrationTester'])->name('integration-tester');
    Route::post('integration-tester/run', [TextPaymentGatewayController::class, 'runIntegrationTest'])->name('integration-tester.run');
    Route::get('verification-secret-page', [TextPaymentGatewayController::class, 'verificationSecretPage'])->name('verification-secret-page');
    Route::post('generate-qr', [TextPaymentGatewayController::class, 'generateQrCode'])->name('generate-qr');
    Route::get('device/{id}', [TextPaymentGatewayController::class, 'showDevice'])->name('device');
    Route::delete('device/{id}', [TextPaymentGatewayController::class, 'deleteDevice'])->name('delete-device');
    Route::delete('device/{id}/transactions', [TextPaymentGatewayController::class, 'clearTransactions'])->name('clear-transactions');
    Route::patch('device/{id}/toggle-spoof-detection', [TextPaymentGatewayController::class, 'toggleSpoofDetection'])->name('toggle-spoof-detection');
    Route::get('device/{deviceId}/transactions', [TextPaymentGatewayController::class, 'getTransactions'])->name('transactions');
    Route::get('verification-secret', [TextPaymentGatewayController::class, 'getVerificationSecret'])->name('verification-secret');
    Route::post('verification-secret/regenerate', [TextPaymentGatewayController::class, 'regenerateVerificationSecret'])->name('verification-secret.regenerate');
    Route::post('webhook', [TextPaymentGatewayController::class, 'updateWebhook'])->name('webhook.update');
    Route::delete('webhook/{id}', [TextPaymentGatewayController::class, 'deleteWebhook'])->name('webhook.delete');
    Route::post('webhook/test', [TextPaymentGatewayController::class, 'testWebhook'])->name('webhook.test');
    Route::get('wallets', [TextPaymentGatewayController::class, 'wallets'])->name('wallets');
    Route::post('wallets', [TextPaymentGatewayController::class, 'storeWallet'])->name('wallets.store');
    Route::delete('wallets/{id}', [TextPaymentGatewayController::class, 'deleteWallet'])->name('wallets.delete');
    Route::get('test-mode', [TextPaymentGatewayController::class, 'testMode'])->name('test-mode');
    Route::post('test-mode/toggle', [TextPaymentGatewayController::class, 'toggleTestMode'])->name('test-mode.toggle');
    Route::post('test-mode/create-transaction', [TextPaymentGatewayController::class, 'createTestTransaction'])->name('test-mode.create-transaction');
    Route::post('test-mode/send-webhook', [TextPaymentGatewayController::class, 'sendTestWebhook'])->name('test-mode.send-webhook');
    Route::delete('test-mode/clear-data', [TextPaymentGatewayController::class, 'clearTestData'])->name('test-mode.clear-data');
});

