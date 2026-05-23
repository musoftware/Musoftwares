<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\AutoSms\Http\Controllers\Api\AutoSmsPaymentHubController;
use Modules\AutoSms\Http\Controllers\Api\AutoSmsOrderController;
use Modules\AutoSms\Http\Controllers\Api\AutoSmsTransactionController;
use Modules\AutoSms\Http\Controllers\Api\AutoSmsWebhookController;

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
    Route::post('/connect', [AutoSmsPaymentHubController::class, 'connect']);

    // Allowed senders (public)
    Route::get('/allowed-senders', [AutoSmsPaymentHubController::class, 'getAllowedSenders']);

    // Receive SMS (public, uses device token)
    Route::post('/sms', [AutoSmsPaymentHubController::class, 'receiveSms']);

    // Debug endpoint (public, for testing parser)
    Route::get('/debug/empty-phone-numbers', [AutoSmsPaymentHubController::class, 'debugEmptyPhoneNumbers']);

    // Public wallet endpoints (no auth required)
    Route::get('/get-random-wallet', [AutoSmsPaymentHubController::class, 'getRandomWallet']);
    Route::post('/verify-payment', [AutoSmsPaymentHubController::class, 'verifyPayment']);
});

// AutoSMS Public API (Bearer token authentication, no Sanctum)
Route::prefix('auto-sms/public')->group(function () {
    // Link order to phone number (public API - Bearer token authentication)
    Route::post('/link-order', [AutoSmsPaymentHubController::class, 'linkOrderPublic']);
});

// AutoSMS Payment Orders API (Sanctum authentication)
Route::middleware('auth:sanctum')->prefix('auto-sms/orders')->group(function () {
    // Create new payment order
    Route::post('/create', [AutoSmsOrderController::class, 'createOrder']);
    // Verify payment order by checking transaction history
    Route::post('/verify/{orderId}', [AutoSmsOrderController::class, 'verifyOrder']);
    // Get order status
    Route::get('/{orderId}', [AutoSmsOrderController::class, 'getOrderStatus']);
    // Get all orders for user
    Route::get('/', [AutoSmsOrderController::class, 'getUserOrders']);
});

// AutoSMS Transaction Verification (requires authentication)
Route::middleware('auth:sanctum')->prefix('auto-sms')->group(function () {
    // Verify transaction by phone number (requires authentication - users can only verify their own transactions)
    Route::post('/verify-transaction', [AutoSmsPaymentHubController::class, 'verifyTransaction']);
    // Link order to phone number (requires authentication - users can only link orders to their own account)
    Route::post('/link-order', [AutoSmsPaymentHubController::class, 'linkOrder']);

    // Transaction History
    Route::get('/transactions', [AutoSmsTransactionController::class, 'index']);
    Route::get('/transactions/{id}', [AutoSmsTransactionController::class, 'show']);

    // Order Management API (requires authentication)
    Route::prefix('orders')->group(function () {
        // Create new order with validation
        Route::post('/create', [AutoSmsOrderController::class, 'create']);
        // Verify payment for an order
        Route::post('/verify-payment', [AutoSmsOrderController::class, 'verifyPayment']);
        // List user orders
        Route::get('/', [AutoSmsOrderController::class, 'index']);
        // Get order details
        Route::get('/{id}', [AutoSmsOrderController::class, 'show']);
        // Cancel order
        Route::post('/{id}/cancel', [AutoSmsOrderController::class, 'cancel']);
    });
});

// AutoSMS Webhook Management (requires authentication)
Route::middleware('auth:sanctum')->prefix('auto-sms/webhooks')->group(function () {
    Route::get('/', [AutoSmsWebhookController::class, 'show']);
    Route::post('/', [AutoSmsWebhookController::class, 'register']);
    Route::put('/{id}', [AutoSmsWebhookController::class, 'update']);
    Route::delete('/{id}', [AutoSmsWebhookController::class, 'destroy']);
    Route::post('/test', [AutoSmsWebhookController::class, 'test']);
});
