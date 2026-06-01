<?php

use Illuminate\Support\Facades\Route;
use Modules\AffiliatePos\Http\Controllers\AffiliatePosController;

Route::middleware(['auth', 'verified', 'feature:affiliate_pos'])->prefix('admin/affiliate-pos')->group(function () {
    Route::get('/pos', [\Modules\AffiliatePos\Http\Controllers\Web\PosController::class, 'index'])->name('affiliate_pos.pos.index');
    
    // Admin Orders
    Route::get('orders', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'index'])->name('affiliate_pos.admin.orders.index');
    Route::get('orders/{order}', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'show'])->name('affiliate_pos.admin.orders.show');
    Route::patch('orders/{order}/status', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'updateStatus'])->name('affiliate_pos.admin.orders.status');
    Route::patch('orders/{order}/partial-delivery', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'updatePartialDelivery'])->name('affiliate_pos.admin.orders.partial_delivery');
    Route::post('orders/bulk-status', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'bulkUpdateStatus'])->name('affiliate_pos.admin.orders.bulk_status');
    Route::post('orders/bulk-shipping', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminOrderController::class, 'bulkAssignShipping'])->name('affiliate_pos.admin.orders.bulk_shipping');
    
    // Admin Payouts
    Route::get('payouts', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminPayoutController::class, 'index'])->name('affiliate_pos.admin.payouts.index');
    Route::post('payouts/{paymentRequest}/process', [\Modules\AffiliatePos\app\Features\OrderManagement\Controllers\AdminPayoutController::class, 'process'])->name('affiliate_pos.admin.payouts.process');
});

Route::middleware(['auth', 'verified', 'feature:affiliate_pos'])->prefix('vendor/affiliate-pos')->group(function () {
    Route::get('orders', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorOrderController::class, 'index'])->name('affiliate_pos.vendor.orders.index');
    Route::get('orders/{order}', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorOrderController::class, 'show'])->name('affiliate_pos.vendor.orders.show');
    Route::get('products', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'index'])->name('affiliate_pos.vendor.products.index');
    Route::get('products/create', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'create'])->name('affiliate_pos.vendor.products.create');
    Route::get('products/{product}/edit', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'edit'])->name('affiliate_pos.vendor.products.edit');
    Route::post('products', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'storeSimple'])->name('affiliate_pos.vendor.products.store');
    Route::put('products/{product}', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'update'])->name('affiliate_pos.vendor.products.update');
    Route::patch('products/{product}/stock', [\Modules\AffiliatePos\app\Features\VendorPortal\Controllers\VendorProductController::class, 'updateStock'])->name('affiliate_pos.vendor.products.stock');
});

Route::middleware(['auth', 'verified', 'feature:affiliate_pos'])->prefix('affiliate/affiliate-pos')->group(function () {
    Route::get('orders', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliateOrderController::class, 'index'])->name('affiliate_pos.affiliate.orders.index');
    Route::get('payouts', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliatePayoutController::class, 'index'])->name('affiliate_pos.affiliate.payouts.index');
    Route::post('payouts', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\AffiliatePayoutController::class, 'requestPayout'])->name('affiliate_pos.affiliate.payouts.store');
    Route::get('moderators', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'index'])->name('affiliate_pos.affiliate.moderators.index');
    Route::get('moderators/create', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'create'])->name('affiliate_pos.affiliate.moderators.create');
    Route::post('moderators', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'store'])->name('affiliate_pos.affiliate.moderators.store');
    Route::delete('moderators/{moderator}', [\Modules\AffiliatePos\app\Features\AffiliateNetwork\Controllers\ModeratorController::class, 'destroy'])->name('affiliate_pos.affiliate.moderators.destroy');
});
