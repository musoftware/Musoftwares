<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\Http\Controllers\Admin\AdminShopCategoryController;
use Modules\Shop\Http\Controllers\Admin\AdminShopImportController;
use Modules\Shop\Http\Controllers\Admin\AdminShopOrderController;
use Modules\Shop\Http\Controllers\Admin\AdminShopProductController;
use Modules\Shop\Http\Controllers\CartController;
use Modules\Shop\Http\Controllers\CheckoutController;
use Modules\Shop\Http\Controllers\ShopSitemapController;
use Modules\Shop\Http\Controllers\StorefrontController;

Route::middleware('web')->group(function () {
    Route::permanentRedirect('/sitemaps/store.xml', '/store/sitemap.xml');

    Route::prefix('store')->name('shop.')->group(function () {
        Route::get('/', [StorefrontController::class, 'home'])->name('home');
        Route::get('/sitemap.xml', [ShopSitemapController::class, 'show'])->name('sitemap');
        Route::get('/search', [StorefrontController::class, 'search'])->middleware('throttle:60,1')->name('search');
        Route::get('/collections/{category:slug}', [StorefrontController::class, 'collection'])->name('collection');
        Route::get('/products/{product:slug}', [StorefrontController::class, 'product'])->name('product');

        Route::get('/cart', [CartController::class, 'show'])->name('cart');
        Route::post('/cart/{product}', [CartController::class, 'add'])->whereNumber('product')->middleware('throttle:60,1')->name('cart.add');
        Route::patch('/cart/lines/{key}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/lines/{key}', [CartController::class, 'remove'])->name('cart.remove');

        Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
        Route::get('/orders/{uuid}/thanks', [CheckoutController::class, 'thanks'])->whereUuid('uuid')->name('thanks');
    });

    Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->prefix('admin/shop')->name('admin.shop.')->group(function () {
        Route::get('/products', [AdminShopProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [AdminShopProductController::class, 'create'])->name('products.create');
        Route::post('/products', [AdminShopProductController::class, 'store'])->name('products.store');
        Route::get('/products/import-template', [AdminShopImportController::class, 'template'])->name('products.template');
        Route::post('/products/import', [AdminShopImportController::class, 'store'])->name('products.import');
        Route::get('/products/{product}/edit', [AdminShopProductController::class, 'edit'])->name('products.edit');
        Route::post('/products/{product}', [AdminShopProductController::class, 'update'])->name('products.update');
        Route::post('/products/{product}/toggle', [AdminShopProductController::class, 'toggle'])->name('products.toggle');
        Route::delete('/products/{product}', [AdminShopProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/categories', [AdminShopCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminShopCategoryController::class, 'store'])->name('categories.store');
        Route::post('/categories/{category}', [AdminShopCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminShopCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/orders', [AdminShopOrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/{order}/status', [AdminShopOrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/orders/{order}/angazny', [AdminShopOrderController::class, 'sendToAngazny'])->name('orders.angazny');
    });
});
