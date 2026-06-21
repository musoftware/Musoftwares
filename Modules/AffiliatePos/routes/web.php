<?php

use Illuminate\Support\Facades\Route;

// ── Merchant (Owner/Admin) Routes ──────────────────────────────────────────
// Changed prefix from '/admin/affiliate-pos' to '/pos/merchant' (no 'admin' in URL)
// Changed middleware from 'feature:' to 'subscription:' (standard SaaS pattern)
Route::middleware(['auth', 'verified', 'subscription:affiliate_pos'])->prefix('pos/merchant')->name('affiliate_pos.admin.')->group(function () {
    Route::get('/pos', [\Modules\AffiliatePos\Http\Controllers\Web\PosController::class, 'index'])->name('pos.index');

    // Orders
    Route::get('orders', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{order}/partial-delivery', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'updatePartialDelivery'])->name('orders.partial_delivery');
    Route::post('orders/bulk-status', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'bulkUpdateStatus'])->name('orders.bulk_status');
    Route::post('orders/bulk-shipping', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'bulkAssignShipping'])->name('orders.bulk_shipping');

    // Payouts
    Route::get('payouts', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::post('payouts/{paymentRequest}/process', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminPayoutController::class, 'process'])->name('payouts.process');
});

// ── Vendor Routes ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'subscription:affiliate_pos'])->prefix('pos/vendor')->name('affiliate_pos.vendor.')->group(function () {
    Route::get('orders', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorOrderController::class, 'show'])->name('orders.show');
    Route::get('products', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'index'])->name('products.index');
    Route::get('products/create', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'create'])->name('products.create');
    Route::get('products/{product}/edit', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'edit'])->name('products.edit');
    Route::post('products', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'storeSimple'])->name('products.store');
    Route::put('products/{product}', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'update'])->name('products.update');
    Route::patch('products/{product}/stock', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'updateStock'])->name('products.stock');
});

// ── Affiliate Routes ───────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'subscription:affiliate_pos'])->prefix('pos/affiliate')->name('affiliate_pos.affiliate.')->group(function () {
    Route::get('orders', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliateOrderController::class, 'index'])->name('orders.index');
    Route::get('payouts', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliatePayoutController::class, 'index'])->name('payouts.index');
    Route::post('payouts', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliatePayoutController::class, 'requestPayout'])->name('payouts.store');
    Route::get('moderators', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'index'])->name('moderators.index');
    Route::get('moderators/create', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'create'])->name('moderators.create');
    Route::post('moderators', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'store'])->name('moderators.store');
    Route::delete('moderators/{moderator}', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'destroy'])->name('moderators.destroy');
});
