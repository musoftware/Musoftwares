<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\DashboardController;
use Modules\Marketplace\Http\Controllers\ServiceController;
use Modules\Marketplace\Http\Controllers\ServiceCategoryController;
use Modules\Marketplace\Http\Controllers\ServiceOrderController;
use Modules\Marketplace\Http\Controllers\CheckoutController;
use Modules\Marketplace\Http\Controllers\DeliverableController;
use Modules\Marketplace\Http\Controllers\FreeDownloadController;
use Modules\Marketplace\Http\Controllers\ServiceSerialController;
use Modules\Marketplace\Http\Controllers\PromotionsController;
use Modules\Marketplace\Http\Controllers\ReferralController;
use Modules\Marketplace\Http\Controllers\WishlistController;
use Modules\Marketplace\Http\Controllers\PremiumToolController;

// Single group — order matters: literal routes BEFORE wildcards
Route::middleware('web')
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {

        // ── Public ────────────────────────────────────────────────────────
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        
        // Navigation API (using controller action instead of route closure)
        Route::get('/api/categories', [ServiceCategoryController::class, 'apiIndex'])->name('categories.api');

        // Free downloads
        Route::post('/downloads/{service}/request', [FreeDownloadController::class, 'requestDownload'])->name('downloads.request');
        Route::get('/downloads/claim/{token}', [FreeDownloadController::class, 'claimDownload'])->name('downloads.claim');

        // ── Auth-only ─────────────────────────────────────────────────────
        Route::middleware('auth')->group(function () {

            // Dashboard
            Route::get('/',         [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard',[DashboardController::class, 'index'])->name('dashboard.alias');

            // Services CRUD
            Route::get('/services/create',  [ServiceController::class, 'create'])->name('services.create');
            Route::post('/services',        [ServiceController::class, 'store'])->name('services.store');

            // Orders Lifecycle
            Route::get('/orders',           [ServiceOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}',   [ServiceOrderController::class, 'show'])->name('orders.show');
            Route::post('/orders',          [ServiceOrderController::class, 'store'])->name('orders.store');
            Route::post('/orders/{order}/complete', [ServiceOrderController::class, 'complete'])->name('orders.complete');
            Route::post('/orders/{order}/dispute',  [ServiceOrderController::class, 'dispute'])->name('orders.dispute');

            // Work Deliverables & Revisions
            Route::post('/orders/{order}/deliver', [DeliverableController::class, 'submitWork'])->name('orders.deliver');
            Route::post('/orders/{order}/revision',[DeliverableController::class, 'requestRevision'])->name('orders.revision');

            // Cart Checkout
            Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');

            // Promotions & Coupons
            Route::post('/coupons/apply', [PromotionsController::class, 'applyCoupon'])->name('coupons.apply');

            // Referral Network & Withdrawals
            Route::post('/referral/withdraw', [ReferralController::class, 'withdraw'])->name('referral.withdraw');

            // Wishlist / Favorites
            Route::get('/favorites', [WishlistController::class, 'index'])->name('favorites.index');
            Route::post('/services/{service}/favorite', [WishlistController::class, 'toggle'])->name('favorites.toggle');


            // AI Tools Marketplace
            Route::post('/tools/{toolSlug}/use', [PremiumToolController::class, 'useTool'])->name('tools.use');
        });

        // ── Wildcard — always last ─────────────────────────────────────────
        Route::get('/services/{id}', [ServiceController::class, 'show'])->name('services.show');
    });

// -- Seller Landing Pages ------------------------------------------
Route::middleware(['web', 'auth'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        // CRUD
        Route::get('/landing-pages', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'index'])->name('landing-pages.index');
        Route::get('/landing-pages/create/{service}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'create'])->name('landing-pages.create');
        Route::post('/landing-pages/{service}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'store'])->name('landing-pages.store');
        Route::get('/landing-pages/{service}/edit/{landingPage?}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'edit'])->name('landing-pages.edit');
        Route::put('/landing-pages/{service}/{landingPage?}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'update'])->name('landing-pages.update');
        Route::post('/landing-pages/{landingPage}/duplicate', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageController::class, 'duplicate'])->name('landing-pages.duplicate');

        // Submissions
        Route::get('/landing-pages/{service}/submissions', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageSubmissionController::class, 'submissions'])->name('landing-pages.submissions');
        Route::delete('/landing-pages/submissions/{submission}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageSubmissionController::class, 'destroySubmission'])->name('landing-pages.submissions.destroy');
        Route::get('/landing-pages/{service}/submissions/export', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageSubmissionController::class, 'exportSubmissions'])->name('landing-pages.submissions.export');

        // Analytics
        Route::get('/landing-pages/{service}/analytics', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAnalyticsController::class, 'analytics'])->name('landing-pages.analytics');

        // AI Generation
        Route::post('/landing-pages/{service}/generate-questions', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAIController::class, 'generateQuestions'])->name('landing-pages.generate-questions');
        Route::post('/landing-pages/{service}/generate-faqs', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAIController::class, 'generateFAQs'])->name('landing-pages.generate-faqs');
        Route::post('/landing-pages/{service}/generate-pricing', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAIController::class, 'generatePricingTables'])->name('landing-pages.generate-pricing');
        Route::post('/landing-pages/{service}/generate-content', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAIController::class, 'generateLandingPageContent'])->name('landing-pages.generate-content');
    });

        // ── Seller Portal ─────────────────────────────────────────────────
Route::middleware(['web', 'auth', 'role:seller'])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {
        Route::get('/dashboard', [\Modules\Marketplace\Http\Controllers\Seller\SellerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/products', [\Modules\Marketplace\Http\Controllers\Seller\SellerPortalController::class, 'products'])->name('products');
        Route::get('/payouts', [\Modules\Marketplace\Http\Controllers\Seller\SellerPortalController::class, 'payouts'])->name('payouts');
        Route::get('/serials', [ServiceSerialController::class, 'index'])->name('serials.index');
        Route::post('/serials', [ServiceSerialController::class, 'store'])->name('serials.store');
        Route::post('/serials/bulk', [ServiceSerialController::class, 'bulkStore'])->name('serials.bulkStore');
    });

// -- Public Landing Page Routes ------------------------------------
Route::middleware(['web'])
    ->name('services.')
    ->group(function () {
        Route::get('/s/{slug}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPagePublicController::class, 'show'])->name('landing-page.show');
        Route::get('/s/preview/{template}', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPagePublicController::class, 'previewTemplate'])->name('landing-page.preview');
        Route::post('/s/{slug}/submit', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageSubmissionController::class, 'submitForm'])->name('landing-page.submit');
        
        // Analytics Tracking endpoints
        Route::post('/s/track/cta', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAnalyticsController::class, 'trackCtaClick'])->name('landing-page.track.cta');
        Route::post('/s/track/scroll', [\Modules\Marketplace\Http\Controllers\Seller\ServiceLandingPageAnalyticsController::class, 'trackScroll'])->name('landing-page.track.scroll');
    });

// -- Admin Routes --------------------------------------------------
Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/marketplace')
    ->name('admin.marketplace.')
    ->group(function () {
        Route::get('/service-landing-pages', [\Modules\Marketplace\Http\Controllers\Admin\AdminServiceLandingPageController::class, 'index'])->name('service-landing-pages.index');
        Route::post('/service-landing-pages/{landingPage}/toggle-status', [\Modules\Marketplace\Http\Controllers\Admin\AdminServiceLandingPageController::class, 'toggleStatus'])->name('service-landing-pages.toggle-status');
        Route::delete('/service-landing-pages/{landingPage}', [\Modules\Marketplace\Http\Controllers\Admin\AdminServiceLandingPageController::class, 'destroy'])->name('service-landing-pages.destroy');
    });
