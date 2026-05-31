<?php

use Illuminate\Support\Facades\Route;
use Modules\SmsPaymentGateway\Http\Controllers\SmsPaymentGatewayController;
use Modules\SmsPaymentGateway\Http\Controllers\WidgetController;
use Modules\SmsPaymentGateway\Http\Controllers\HostedCheckoutController;

// ─── Public Widget Routes (Legacy) ─────────────────
Route::middleware(['web'])->prefix('sms-payment-gateway')->name('sms-payment-gateway.')->group(function () {
    Route::get('checkout/{uuid}', [WidgetController::class, 'show'])->name('widget.show');
    Route::get('checkout/{uuid}/status', [WidgetController::class, 'status'])->name('widget.status');
    Route::post('checkout/{uuid}/verify', [WidgetController::class, 'verify'])->name('widget.verify');
});

// ─── Hosted Checkout (Stripe-like, public) ─────────
Route::middleware(['web'])->group(function () {
    Route::get('/pay/{sessionId}', [HostedCheckoutController::class, 'show'])->name('sms-gateway.checkout.show');
    Route::post('/pay/{sessionId}/verify', [HostedCheckoutController::class, 'verify'])->name('sms-gateway.checkout.verify');
    Route::get('/pay/{sessionId}/status', [HostedCheckoutController::class, 'status'])->name('sms-gateway.checkout.status');
    
    Route::get('/js/smspay.js', function () {
        $path = module_path('SmsPaymentGateway', 'resources/assets/js/smspay.js');
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path, ['Content-Type' => 'application/javascript']);
    })->name('sms-gateway.widget.js');
});

// ─── Authenticated Dashboard Routes ────────────────
Route::middleware(['web', 'auth', 'verified', 'onboarding', 'subscription:sms-payment-gateway'])->prefix('sms-payment-gateway')->name('sms-payment-gateway.')->group(function () {
    Route::get('/', [SmsPaymentGatewayController::class, 'index'])->name('index');
    Route::get('devices', [SmsPaymentGatewayController::class, 'devices'])->name('devices');
    Route::get('transactions', [SmsPaymentGatewayController::class, 'transactions'])->name('transactions');
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

    Route::get('device/{deviceId}/transactions', [SmsPaymentGatewayController::class, 'getTransactions'])->name('transactions');
    Route::get('verification-secret', [SmsPaymentGatewayController::class, 'getVerificationSecret'])->name('verification-secret');
    Route::post('verification-secret/regenerate', [SmsPaymentGatewayController::class, 'regenerateVerificationSecret'])->name('verification-secret.regenerate');
    Route::post('webhook', [SmsPaymentGatewayController::class, 'updateWebhook'])->name('webhook.update');
    Route::delete('webhook/{id}', [SmsPaymentGatewayController::class, 'deleteWebhook'])->name('webhook.delete');
    Route::post('webhook/test', [SmsPaymentGatewayController::class, 'testWebhook'])->name('webhook.test');
    Route::get('settings', [SmsPaymentGatewayController::class, 'settings'])->name('settings');
    Route::post('settings', [SmsPaymentGatewayController::class, 'storeSettings'])->name('settings.store');
    Route::get('payment-links', [SmsPaymentGatewayController::class, 'paymentLinks'])->name('payment-links');
    Route::post('payment-links', [SmsPaymentGatewayController::class, 'createPaymentLink'])->name('payment-links.store');
    Route::get('test-mode', [SmsPaymentGatewayController::class, 'testMode'])->name('test-mode');
    Route::post('test-mode/toggle', [SmsPaymentGatewayController::class, 'toggleTestMode'])->name('test-mode.toggle');
    Route::post('test-mode/create-transaction', [SmsPaymentGatewayController::class, 'createTestTransaction'])->name('test-mode.create-transaction');
    Route::post('test-mode/send-webhook', [SmsPaymentGatewayController::class, 'sendTestWebhook'])->name('test-mode.send-webhook');
    Route::delete('test-mode/clear-data', [SmsPaymentGatewayController::class, 'clearTestData'])->name('test-mode.clear-data');

    Route::get('sms-simulator', [SmsPaymentGatewayController::class, 'smsSimulator'])->name('sms-simulator');

    Route::get('checkout-sessions', [SmsPaymentGatewayController::class, 'checkoutSessions'])->name('checkout-sessions');

    // ─── API Keys Management ───────────────────────
    Route::get('api-keys', [SmsPaymentGatewayController::class, 'apiKeys'])->name('api-keys');
    Route::post('api-keys', [SmsPaymentGatewayController::class, 'createApiKey'])->name('api-keys.store');
    Route::delete('api-keys/{id}', [SmsPaymentGatewayController::class, 'deleteApiKey'])->name('api-keys.delete');
    Route::post('api-keys/{id}/roll', [SmsPaymentGatewayController::class, 'rollApiKey'])->name('api-keys.roll');
});


