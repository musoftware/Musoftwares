<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentGateway\Http\Controllers\Api\PaymentGatewayApiController;

/*
|--------------------------------------------------------------------------
| Public API Routes — Musoftware Payment Gateway
|--------------------------------------------------------------------------
|
| Merchants integrate with these endpoints using their client_id + client_secret.
|
| Authentication:
|   Header: X-Client-Id: pgw_xxxxx
|   Header: X-Client-Secret: sk_xxxxx
|
| Base URL: /api/payment-gateway/
|
*/

Route::prefix('payment-gateway')->name('payment-gateway.')->group(function () {

    // ── Payment Initiation ────────────────────────────────────────────────
    // POST /api/payment-gateway/initiate
    // Merchant sends order details → receives Kashier payment URL
    Route::post('/initiate', [PaymentGatewayApiController::class, 'initiate'])
        ->name('initiate');

    // ── Payment Status Check ──────────────────────────────────────────────
    // GET /api/payment-gateway/status/{order_id}
    // Merchant polls status using internal or external order ID
    Route::get('/status/{orderId}', [PaymentGatewayApiController::class, 'status'])
        ->name('status');

    // ── Kashier Server Webhook (called by Kashier, NOT the merchant) ──────
    // POST /api/payment-gateway/webhook/kashier
    Route::post('/webhook/kashier', [PaymentGatewayApiController::class, 'kashierWebhook'])
        ->name('webhook.kashier')
        ->withoutMiddleware(['api']); // No CSRF or auth — Kashier calls this directly

    // ── Customer Redirect URLs (called by Kashier browser redirect) ───────
    // GET /api/payment-gateway/webhook/success/{internalOrderId}
    Route::get('/webhook/success/{internalOrderId}', [PaymentGatewayApiController::class, 'kashierSuccess'])
        ->name('webhook.success');

    // GET /api/payment-gateway/webhook/failure/{internalOrderId}
    Route::get('/webhook/failure/{internalOrderId}', [PaymentGatewayApiController::class, 'kashierFailure'])
        ->name('webhook.failure');
});
