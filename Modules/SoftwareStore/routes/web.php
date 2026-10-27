<?php

use Illuminate\Support\Facades\Route;
use Modules\SoftwareStore\Http\Controllers\Admin\AdminMarketingController;
use Modules\SoftwareStore\Http\Controllers\Admin\AdminSoftwareProductController;
use Modules\SoftwareStore\Http\Controllers\FreeToolController;
use Modules\SoftwareStore\Http\Controllers\SoftwareOrderController;
use Modules\SoftwareStore\Http\Controllers\SoftwareProductController;
use Modules\SoftwareStore\Http\Controllers\SoftwareUpdateFeedController;
use Modules\SoftwareStore\Http\Controllers\WhiteLabelController;
use Modules\SoftwareStore\Http\Controllers\WhiteLabelStoreController;

Route::middleware('web')->group(function () {

    // ── Public landing pages ────────────────────────────────────────
    Route::get('/services', [SoftwareProductController::class, 'index'])->name('services.index');

    // ── Free tools (listed in /services) ────────────────────────────
    Route::redirect('/services/tools', '/services#tools', 301);
    Route::get('/services/tools/{slug}', [FreeToolController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('services.tools.show');
    Route::post('/services/tools/qr-menu', [FreeToolController::class, 'storeMenu'])->middleware('throttle:5,10')->name('services.tools.menu.store');
    Route::put('/services/tools/qr-menu/{code}', [FreeToolController::class, 'updateMenu'])->middleware('throttle:30,10')->name('services.tools.menu.update');
    Route::get('/services/tools/qr-menu/{code}/data', [FreeToolController::class, 'menuData'])->middleware('throttle:30,1')->name('services.tools.menu.data');
    Route::get('/menu/{code}', [FreeToolController::class, 'menu'])->where('code', '[a-z0-9]{8}')->name('services.tools.menu');
    Route::get('/services/{slug}.md', [SoftwareProductController::class, 'markdown'])->where('slug', '[a-z0-9-]+')->name('software.markdown');
    Route::get('/services/{slug}', [SoftwareProductController::class, 'show'])->name('website-services.show');

    // ── Guest order, payment and download ───────────────────────────
    Route::post('/services/{slug}/order', [SoftwareOrderController::class, 'store'])->middleware('throttle:10,1')->name('software.orders.store');
    Route::get('/software-orders/{uuid}/resume', [SoftwareOrderController::class, 'resume'])->middleware(['signed', 'throttle:20,1'])->name('software.orders.resume');
    Route::get('/software-orders/{uuid}/success', [SoftwareOrderController::class, 'success'])->name('software.orders.success');
    Route::get('/software-orders/{uuid}/failure', [SoftwareOrderController::class, 'failure'])->name('software.orders.failure');
    Route::get('/software-orders/{uuid}/download', [SoftwareOrderController::class, 'download'])->name('software.orders.download');
    Route::post('/software-orders/webhook', [SoftwareOrderController::class, 'webhook'])->name('software.orders.webhook');

    // ── White label: partners sell our programs under their own name ─
    Route::get('/white-label', [WhiteLabelController::class, 'landing'])->name('white-label');
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/white-label/settings', [WhiteLabelController::class, 'settings'])->name('white-label.settings');
        Route::post('/white-label/settings', [WhiteLabelController::class, 'save'])->name('white-label.save');
        Route::post('/white-label/subscribe', [WhiteLabelController::class, 'subscribe'])->middleware('throttle:5,1')->name('white-label.subscribe');
    });
    // the partner's store; the list page may be framed on the partner's site (RemoveSecurityHeaders)
    Route::get('/shop/{partner}', [WhiteLabelStoreController::class, 'index'])->name('white-label.store');
    Route::get('/shop/{partner}/{slug}', [WhiteLabelStoreController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('white-label.product');
    Route::post('/shop/{partner}/{slug}/order', [WhiteLabelStoreController::class, 'order'])->where('slug', '[a-z0-9-]+')->middleware('throttle:10,1')->name('white-label.order');

    // ── Auto-update feed read by electron-updater (generic provider) ─
    Route::get('/updates/{slug}/latest.yml', [SoftwareUpdateFeedController::class, 'latest'])->middleware('throttle:60,1')->name('software.updates.latest');
    Route::get('/updates/{slug}/{file}', [SoftwareUpdateFeedController::class, 'file'])->middleware('throttle:30,1')->name('software.updates.file');

    // ── Admin ───────────────────────────────────────────────────────
    Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/marketing', [AdminMarketingController::class, 'index'])->name('marketing.index');
        Route::put('/marketing/discount', [AdminMarketingController::class, 'updateDiscount'])->name('marketing.discount');
        Route::get('/software-products', [AdminSoftwareProductController::class, 'index'])->name('software.index');
        Route::get('/software-products/{product}/edit', [AdminSoftwareProductController::class, 'edit'])->name('software.edit');
        Route::post('/software-products/{product}', [AdminSoftwareProductController::class, 'update'])->name('software.update');
        Route::post('/software-products/{product}/toggle', [AdminSoftwareProductController::class, 'toggleActive'])->name('software.toggle');
        Route::post('/software-products/{product}/releases', [AdminSoftwareProductController::class, 'storeRelease'])->name('software.releases.store');
        Route::post('/software-releases/{release}/toggle', [AdminSoftwareProductController::class, 'toggleRelease'])->name('software.releases.toggle');
        Route::delete('/software-releases/{release}', [AdminSoftwareProductController::class, 'destroyRelease'])->name('software.releases.destroy');
    });
});
