<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayPaymentHubController;
use Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayOrderController;
use Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayTransactionController;
use Modules\SmsPaymentGateway\Http\Controllers\Api\SmsPaymentGatewayWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ==============================================
// AUTO SMS PAYMENT HUB API ROUTES
// ==============================================

Route::prefix('sms-payment-gateway')->group(function () {
    // Device connection (public, uses connection code)
    Route::post('/connect', [SmsPaymentGatewayPaymentHubController::class, 'connect']);

    // Allowed senders (public)
    Route::get('/allowed-senders', [SmsPaymentGatewayPaymentHubController::class, 'getAllowedSenders']);

    // Receive SMS (public, uses device token)
    Route::post('/sms', [SmsPaymentGatewayPaymentHubController::class, 'receiveSms']);

    // Debug endpoint (public, for testing parser)
    Route::get('/debug/empty-phone-numbers', [SmsPaymentGatewayPaymentHubController::class, 'debugEmptyPhoneNumbers']);

    // Public wallet endpoints (no auth required)
    Route::get('/get-random-wallet', [SmsPaymentGatewayPaymentHubController::class, 'getRandomWallet']);
    Route::post('/verify-payment', [SmsPaymentGatewayPaymentHubController::class, 'verifyPayment']);
});

// AutoSMS Public API (Bearer token authentication, no Sanctum)
Route::prefix('sms-payment-gateway/public')->group(function () {
    // Link order to phone number (public API - Bearer token authentication)
    Route::post('/link-order', [SmsPaymentGatewayPaymentHubController::class, 'linkOrderPublic']);
});

// AutoSMS Payment Orders API (Sanctum authentication)
Route::middleware('auth:sanctum')->prefix('sms-payment-gateway/orders')->group(function () {
    // Create new payment order
    Route::post('/create', [SmsPaymentGatewayOrderController::class, 'createOrder']);
    // Verify payment order by checking transaction history
    Route::post('/verify/{orderId}', [SmsPaymentGatewayOrderController::class, 'verifyOrder']);
    // Get order status
    Route::get('/{orderId}', [SmsPaymentGatewayOrderController::class, 'getOrderStatus']);
    // Get all orders for user
    Route::get('/', [SmsPaymentGatewayOrderController::class, 'getUserOrders']);
});

// AutoSMS Transaction Verification (requires authentication)
Route::middleware('auth:sanctum')->prefix('sms-payment-gateway')->group(function () {
    // Verify transaction by phone number (requires authentication - users can only verify their own transactions)
    Route::post('/verify-transaction', [SmsPaymentGatewayPaymentHubController::class, 'verifyTransaction']);
    // Link order to phone number (requires authentication - users can only link orders to their own account)
    Route::post('/link-order', [SmsPaymentGatewayPaymentHubController::class, 'linkOrder']);

    // Transaction History
    Route::get('/transactions', [SmsPaymentGatewayTransactionController::class, 'index']);
    Route::get('/transactions/{id}', [SmsPaymentGatewayTransactionController::class, 'show']);

    // Order Management API (requires authentication)
    Route::prefix('orders')->group(function () {
        // Create new order with validation
        Route::post('/create', [SmsPaymentGatewayOrderController::class, 'create']);
        // Verify payment for an order
        Route::post('/verify-payment', [SmsPaymentGatewayOrderController::class, 'verifyPayment']);
        // List user orders
        Route::get('/', [SmsPaymentGatewayOrderController::class, 'index']);
        // Get order details
        Route::get('/{id}', [SmsPaymentGatewayOrderController::class, 'show']);
        // Cancel order
        Route::post('/{id}/cancel', [SmsPaymentGatewayOrderController::class, 'cancel']);
    });
});

// AutoSMS Webhook Management (requires authentication)
Route::middleware('auth:sanctum')->prefix('sms-payment-gateway/webhooks')->group(function () {
    Route::get('/', [SmsPaymentGatewayWebhookController::class, 'show']);
    Route::post('/', [SmsPaymentGatewayWebhookController::class, 'register']);
    Route::put('/{id}', [SmsPaymentGatewayWebhookController::class, 'update']);
    Route::delete('/{id}', [SmsPaymentGatewayWebhookController::class, 'destroy']);
    Route::post('/test', [SmsPaymentGatewayWebhookController::class, 'test']);
});
