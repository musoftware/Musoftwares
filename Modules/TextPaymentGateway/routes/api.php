<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\TextPaymentGateway\Http\Controllers\Api\TextPaymentGatewayPaymentHubController;
use Modules\TextPaymentGateway\Http\Controllers\Api\TextPaymentGatewayOrderController;
use Modules\TextPaymentGateway\Http\Controllers\Api\TextPaymentGatewayTransactionController;
use Modules\TextPaymentGateway\Http\Controllers\Api\TextPaymentGatewayWebhookController;

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

Route::prefix('auto-sms')->group(function () {
    // Device connection (public, uses connection code)
    Route::post('/connect', [TextPaymentGatewayPaymentHubController::class, 'connect']);

    // Allowed senders (public)
    Route::get('/allowed-senders', [TextPaymentGatewayPaymentHubController::class, 'getAllowedSenders']);

    // Receive SMS (public, uses device token)
    Route::post('/sms', [TextPaymentGatewayPaymentHubController::class, 'receiveSms']);

    // Debug endpoint (public, for testing parser)
    Route::get('/debug/empty-phone-numbers', [TextPaymentGatewayPaymentHubController::class, 'debugEmptyPhoneNumbers']);

    // Public wallet endpoints (no auth required)
    Route::get('/get-random-wallet', [TextPaymentGatewayPaymentHubController::class, 'getRandomWallet']);
    Route::post('/verify-payment', [TextPaymentGatewayPaymentHubController::class, 'verifyPayment']);
});

// AutoSMS Public API (Bearer token authentication, no Sanctum)
Route::prefix('auto-sms/public')->group(function () {
    // Link order to phone number (public API - Bearer token authentication)
    Route::post('/link-order', [TextPaymentGatewayPaymentHubController::class, 'linkOrderPublic']);
});

// AutoSMS Payment Orders API (Sanctum authentication)
Route::middleware('auth:sanctum')->prefix('auto-sms/orders')->group(function () {
    // Create new payment order
    Route::post('/create', [TextPaymentGatewayOrderController::class, 'createOrder']);
    // Verify payment order by checking transaction history
    Route::post('/verify/{orderId}', [TextPaymentGatewayOrderController::class, 'verifyOrder']);
    // Get order status
    Route::get('/{orderId}', [TextPaymentGatewayOrderController::class, 'getOrderStatus']);
    // Get all orders for user
    Route::get('/', [TextPaymentGatewayOrderController::class, 'getUserOrders']);
});

// AutoSMS Transaction Verification (requires authentication)
Route::middleware('auth:sanctum')->prefix('auto-sms')->group(function () {
    // Verify transaction by phone number (requires authentication - users can only verify their own transactions)
    Route::post('/verify-transaction', [TextPaymentGatewayPaymentHubController::class, 'verifyTransaction']);
    // Link order to phone number (requires authentication - users can only link orders to their own account)
    Route::post('/link-order', [TextPaymentGatewayPaymentHubController::class, 'linkOrder']);

    // Transaction History
    Route::get('/transactions', [TextPaymentGatewayTransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TextPaymentGatewayTransactionController::class, 'show']);

    // Order Management API (requires authentication)
    Route::prefix('orders')->group(function () {
        // Create new order with validation
        Route::post('/create', [TextPaymentGatewayOrderController::class, 'create']);
        // Verify payment for an order
        Route::post('/verify-payment', [TextPaymentGatewayOrderController::class, 'verifyPayment']);
        // List user orders
        Route::get('/', [TextPaymentGatewayOrderController::class, 'index']);
        // Get order details
        Route::get('/{id}', [TextPaymentGatewayOrderController::class, 'show']);
        // Cancel order
        Route::post('/{id}/cancel', [TextPaymentGatewayOrderController::class, 'cancel']);
    });
});

// AutoSMS Webhook Management (requires authentication)
Route::middleware('auth:sanctum')->prefix('auto-sms/webhooks')->group(function () {
    Route::get('/', [TextPaymentGatewayWebhookController::class, 'show']);
    Route::post('/', [TextPaymentGatewayWebhookController::class, 'register']);
    Route::put('/{id}', [TextPaymentGatewayWebhookController::class, 'update']);
    Route::delete('/{id}', [TextPaymentGatewayWebhookController::class, 'destroy']);
    Route::post('/test', [TextPaymentGatewayWebhookController::class, 'test']);
});
