<?php

use Illuminate\Support\Facades\Route;

// Storefront & Cart Routes
Route::middleware(['api', 'feature:affiliate_pos'])->prefix('api/v1/affiliate-pos/storefront')->group(function () {
    Route::get('cart', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController::class, 'index']);
    Route::post('cart', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController::class, 'add']);
    Route::put('cart/{itemId}', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController::class, 'update']);
    Route::patch('cart/{itemId}/commission', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController::class, 'updateCommission']);
    Route::delete('cart/{itemId}', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\CartController::class, 'remove']);
    
    Route::post('checkout', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\CheckoutController::class, 'process']);
});

// Admin Order Management
Route::middleware(['auth:sanctum', 'feature:affiliate_pos'])->prefix('api/v1/affiliate-pos/admin')->group(function () {
    Route::get('orders', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'index']);
    Route::get('orders/{order}', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'show']);
    Route::patch('orders/{order}/status', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'updateStatus']);
    Route::patch('orders/{order}/partial-delivery', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'updatePartialDelivery']);
    Route::post('orders/bulk-status', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'bulkUpdateStatus']);
    Route::post('orders/bulk-shipping', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'bulkAssignShipping']);
});

// Affiliate Network
Route::middleware(['auth:sanctum', 'feature:affiliate_pos'])->prefix('api/v1/affiliate-pos/affiliate')->group(function () {
    Route::get('orders', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliateOrderController::class, 'index']);
});

// Vendor Portal
Route::middleware(['auth:sanctum', 'feature:affiliate_pos'])->prefix('api/v1/affiliate-pos/vendor')->group(function () {
    Route::get('orders', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorOrderController::class, 'index']);
    
    // Vendor Products
    Route::get('products', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'index']);
    Route::post('products/simple', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'storeSimple']);
    Route::post('products/{product}/stock', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'updateStock']);
});

// Payouts (Admin)
Route::middleware(['auth:sanctum', 'feature:affiliate_pos'])->prefix('api/v1/affiliate-pos/admin/payouts')->group(function () {
    Route::get('/', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminPayoutController::class, 'index']);
    Route::post('{paymentRequest}/process', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminPayoutController::class, 'process']);
});

// Payouts & Moderators (Affiliate)
Route::middleware(['auth:sanctum', 'feature:affiliate_pos'])->prefix('api/v1/affiliate-pos/affiliate')->group(function () {
    Route::get('payouts', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliatePayoutController::class, 'index']);
    Route::post('payouts', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliatePayoutController::class, 'requestPayout']);
    
    // Moderators
    Route::get('moderators', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'index']);
    Route::post('moderators', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'store']);
    Route::delete('moderators/{moderator}', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'destroy']);
});

// Public Storefront Catalog
Route::middleware(['api', 'feature:affiliate_pos'])->prefix('api/v1/affiliate-pos/storefront')->group(function () {
    Route::get('products', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController::class, 'index']);
    Route::get('products/{product}', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController::class, 'show']);
    Route::get('categories', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController::class, 'categories']);
    
    Route::get('governorates', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\GeographyController::class, 'governorates']);
    Route::get('governorates/{governorate}/cities', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\GeographyController::class, 'cities']);
});
