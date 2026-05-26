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

// Admin routes moved to web.php for Inertia UI

// Vendor and Affiliate routes moved to web.php for Inertia UI

// Public Storefront Catalog
Route::middleware(['api', 'feature:affiliate_pos'])->prefix('api/v1/affiliate-pos/storefront')->group(function () {
    Route::get('products', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController::class, 'index']);
    Route::get('products/{product}', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController::class, 'show']);
    Route::get('categories', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\ShopController::class, 'categories']);
    
    Route::get('governorates', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\GeographyController::class, 'governorates']);
    Route::get('governorates/{governorate}/cities', [\Modules\AffiliatePos\app\Features\Storefront\Controllers\GeographyController::class, 'cities']);
});
