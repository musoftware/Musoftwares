<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Public/Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'onboarding'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding-wizard', [\App\Http\Controllers\OnboardingController::class, 'show'])->name('onboarding.wizard');
    Route::post('/onboarding-wizard', [\App\Http\Controllers\OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('/product-tour/status', [\App\Http\Controllers\OnboardingController::class, 'updateTourStatus'])->name('product-tour.status');
});

// ERP Routes
Route::middleware(['auth', 'verified', 'onboarding', 'subscription:erp'])->prefix('erp')->name('erp.')->group(function () {
    Route::get('/dashboard', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('dashboard');
    Route::post('/clients', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'storeClient'])->name('clients.store');
    Route::put('/clients/{client}', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'updateClient'])->name('clients.update');
    Route::delete('/clients/{client}', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'destroyClient'])->name('clients.destroy');
    Route::get('/onboarding', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'completeOnboarding'])->name('onboarding.store');
    Route::get('/invoices', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{invoice}', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/invoices/{invoice}/send', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('/invoices/{invoice}/mark-paid', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
    Route::post('/invoices/{invoice}/partial-payment', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'partialPayment'])->name('invoices.partialPayment');
    Route::post('/invoices/{invoice}/cancel', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('/invoices/{invoice}/duplicate', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::get('/invoices/{invoice}/pdf', [\Modules\ERP\Http\Controllers\InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

    // Wallet
    Route::get('/wallet/add-balance', [\Modules\ERP\Http\Controllers\WalletController::class, 'addBalance'])->name('wallet.add-balance');
    Route::post('/wallet/deposit', [\Modules\ERP\Http\Controllers\WalletController::class, 'deposit'])->name('wallet.deposit');
    Route::get('/clients/{client}/wallet', [\Modules\ERP\Http\Controllers\WalletController::class, 'show'])->name('wallet.show');
    Route::get('/clients/{client}/wallet/transactions', [\Modules\ERP\Http\Controllers\WalletController::class, 'transactions'])->name('wallet.transactions');
    Route::post('/clients/{client}/wallet/credit', [\Modules\ERP\Http\Controllers\WalletController::class, 'manualCredit'])->name('wallet.credit');
    Route::post('/clients/{client}/wallet/debit', [\Modules\ERP\Http\Controllers\WalletController::class, 'manualDebit'])->name('wallet.debit');
    Route::post('/clients/{client}/wallet/lock', [\Modules\ERP\Http\Controllers\WalletController::class, 'lockFunds'])->name('wallet.lock');
    Route::post('/clients/{client}/wallet/unlock', [\Modules\ERP\Http\Controllers\WalletController::class, 'unlockFunds'])->name('wallet.unlock');

    // Withdrawals
    Route::get('/withdrawals', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/withdrawals', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::post('/withdrawals/{withdrawal}/approve', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/mark-paid', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'markPaid'])->name('withdrawals.markPaid');
    Route::post('/withdrawals/{withdrawal}/reject', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::post('/withdrawals/{withdrawal}/cancel', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');

    // Payment Methods
    Route::get('/payment-methods', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/payment-methods', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::patch('/payment-methods/{payment_method}', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'update'])->name('payment-methods.update');
    Route::delete('/payment-methods/{payment_method}', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
    Route::post('/payment-methods/{payment_method}/approve', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'approve'])->name('payment-methods.approve');
    Route::post('/payment-methods/{payment_method}/reject', [\Modules\ERP\Http\Controllers\PaymentMethodController::class, 'reject'])->name('payment-methods.reject');

    // Referrals
    Route::get('/referrals', [\Modules\ERP\Http\Controllers\ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/tree/{client}', [\Modules\ERP\Http\Controllers\ReferralController::class, 'tree'])->name('referrals.tree');
    Route::get('/referrals/earnings', [\Modules\ERP\Http\Controllers\ReferralController::class, 'earnings'])->name('referrals.earnings');

    // Recurring
    Route::resource('recurring', \Modules\ERP\Http\Controllers\RecurringController::class);
    Route::post('/recurring/{recurring}/pause', [\Modules\ERP\Http\Controllers\RecurringController::class, 'pause'])->name('recurring.pause');
    Route::post('/recurring/{recurring}/resume', [\Modules\ERP\Http\Controllers\RecurringController::class, 'resume'])->name('recurring.resume');
    Route::get('/recurring/{recurring}/logs', [\Modules\ERP\Http\Controllers\RecurringController::class, 'logs'])->name('recurring.logs');
});

// Freelance Routes
Route::middleware(['auth', 'verified', 'onboarding', 'subscription:freelance'])->prefix('freelance')->name('freelance.')->group(function () {
    Route::get('/dashboard', [\Modules\Freelance\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Skills
    Route::resource('skills', \Modules\Freelance\Http\Controllers\SkillController::class)->except(['create', 'show', 'edit']);

    // User Skills
    Route::post('/user-skills', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'store'])->name('user-skills.store');
    Route::delete('/user-skills/{skill_id}', [\Modules\Freelance\Http\Controllers\UserSkillController::class, 'destroy'])->name('user-skills.destroy');

    // Points
    Route::get('/points', function(Illuminate\Http\Request $request) {
        $packages = \Modules\Freelance\Models\PointPackage::all();
        $transactions = \Modules\Freelance\Models\PointTransaction::where('user_id', $request->user()->id)->latest()->paginate(10);
        return Inertia::render('Freelance/Points/Index', ['packages' => $packages, 'transactions' => $transactions]);
    })->name('points.index');
    Route::resource('point-packages', \Modules\Freelance\Http\Controllers\PointPackageController::class)->except(['create', 'show', 'edit']);
    Route::post('/point-purchases', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'store'])->name('point-purchases.store');
    Route::post('/point-purchases/wallet', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'storeWallet'])->name('point-purchases.store-wallet');
    Route::get('/point-purchases/success', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'success'])->name('point-purchases.success');
    Route::get('/point-purchases/failure', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'failure'])->name('point-purchases.failure');

    // Jobs
    Route::get('/jobs/browse', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'index'])->name('jobs.browse');
    Route::get('/jobs/my-jobs', [\Modules\Freelance\Http\Controllers\FreelanceJobController::class, 'myJobs'])->name('my-jobs');
    Route::resource('jobs', \Modules\Freelance\Http\Controllers\FreelanceJobController::class)->except(['index']);

    // Proposals
    Route::post('/jobs/{job}/proposals', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'store'])->name('proposals.store');
    Route::post('/proposals/{proposal}/accept', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'accept'])->name('proposals.accept');
    Route::post('/proposals/{proposal}/reject', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'reject'])->name('proposals.reject');
    Route::delete('/proposals/{proposal}/withdraw', [\Modules\Freelance\Http\Controllers\ProposalController::class, 'withdraw'])->name('proposals.withdraw');

    // Contracts
    Route::get('/contracts/{contract}', [\Modules\Freelance\Http\Controllers\ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/complete', [\Modules\Freelance\Http\Controllers\ContractController::class, 'complete'])->name('contracts.complete');
    Route::post('/contracts/{contract}/dispute', [\Modules\Freelance\Http\Controllers\ContractController::class, 'dispute'])->name('contracts.dispute');
});

// Marketplace Routes (Public/Logged in)
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/services', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{id}', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'show'])->name('services.show');
});

// Marketplace Authenticated Routes
Route::middleware(['auth', 'verified', 'onboarding'])->prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/dashboard', [\Modules\Marketplace\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::post('/services', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'store'])->name('services.store');

    // Packages
    Route::post('/services/{service}/packages', [\Modules\Marketplace\Http\Controllers\ServicePackageController::class, 'store'])->name('packages.store');
    Route::put('/services/{service}/packages/{package}', [\Modules\Marketplace\Http\Controllers\ServicePackageController::class, 'update'])->name('packages.update')->scopeBindings();
    Route::delete('/services/{service}/packages/{package}', [\Modules\Marketplace\Http\Controllers\ServicePackageController::class, 'destroy'])->name('packages.destroy')->scopeBindings();

    // Orders
    Route::get('/orders', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/deliver', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('/orders/{order}/complete', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/dispute', [\Modules\Marketplace\Http\Controllers\ServiceOrderController::class, 'dispute'])->name('orders.dispute');

    // Messages
    Route::post('/orders/{order}/messages', [\Modules\Marketplace\Http\Controllers\OrderMessageController::class, 'store'])->name('orders.messages.store');
});

// Marketplace Admin Routes
Route::middleware(['auth', 'verified', 'onboarding', 'role:admin'])->prefix('admin/marketplace')->name('admin.marketplace.')->group(function () {
    // Categories
    Route::get('/categories', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'destroy'])->name('categories.destroy');

    // Services Admin Actions
    Route::post('/services/{id}/approve', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'approve'])->name('services.approve');
    Route::post('/services/{id}/reject', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'reject'])->name('services.reject');
    Route::post('/services/{id}/feature', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'feature'])->name('services.feature');

    // Admin Views mapping to components later
    Route::get('/pending-services', function () {
        return Inertia::render('Admin/Marketplace/Pending', [
            'services' => \Modules\Marketplace\Models\Service::with(['seller', 'category'])->where('status', 'draft')->paginate(15)
        ]);
    })->name('services.pending');

    Route::get('/all-services', function () {
        return Inertia::render('Admin/Marketplace/All', [
            'services' => \Modules\Marketplace\Models\Service::with(['seller', 'category'])->paginate(15)
        ]);
    })->name('services.all');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'onboarding'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Reports
    Route::get('/reports/pnl', [\App\Http\Controllers\Admin\ReportController::class, 'pnl'])->name('reports.pnl');

    // Clients
    Route::get('/clients', [\App\Http\Controllers\Admin\ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/{id}', [\App\Http\Controllers\Admin\ClientController::class, 'show'])->name('clients.show');

    // KYC Review
    Route::get('/kyc', [\App\Http\Controllers\Admin\KycController::class, 'index'])->name('kyc.index');
    Route::post('/kyc/{id}/approve', [\App\Http\Controllers\Admin\KycController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{id}/reject', [\App\Http\Controllers\Admin\KycController::class, 'reject'])->name('kyc.reject');
});

// SaaS Subscription & Billing Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/subscriptions/plans', [\App\Http\Controllers\SubscriptionController::class, 'plans'])->name('subscriptions.plans');
    Route::post('/subscriptions/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::get('/subscriptions/manage', [\App\Http\Controllers\SubscriptionController::class, 'manage'])->name('subscriptions.manage');
    Route::post('/subscriptions/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/renew', [\App\Http\Controllers\SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('/subscriptions/kashier/checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkoutKashier'])->name('subscriptions.kashier.checkout');
    Route::get('/subscriptions/kashier/success', [\App\Http\Controllers\SubscriptionController::class, 'kashierSuccess'])->name('subscriptions.kashier.success');
    Route::get('/subscriptions/kashier/failure', [\App\Http\Controllers\SubscriptionController::class, 'kashierFailure'])->name('subscriptions.kashier.failure');
});

require __DIR__.'/auth.php';

// Support Ticket Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tickets', [\App\Http\Controllers\SupportTicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [\App\Http\Controllers\SupportTicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/{id}/resolve', [\App\Http\Controllers\SupportTicketController::class, 'resolve'])->name('tickets.resolve');
});

// KYC Routes
Route::middleware(['auth', 'verified'])->prefix('kyc')->name('kyc.')->group(function () {
    Route::get('/', [\App\Http\Controllers\KycController::class, 'index'])->name('index');
    Route::post('/upload', [\App\Http\Controllers\KycController::class, 'uploadDocument'])->name('upload');
    Route::post('/submit', [\App\Http\Controllers\KycController::class, 'submit'])->name('submit');
    Route::delete('/{id}', [\App\Http\Controllers\KycController::class, 'deleteDocument'])->name('delete');
    Route::get('/{id}/download', [\App\Http\Controllers\KycController::class, 'downloadDocument'])->name('download');
});

// Core Financial Routes
Route::middleware(['auth', 'verified'])->prefix('financial')->name('financial.')->group(function () {
    Route::get('/transactions', [\App\Http\Controllers\FinancialController::class, 'transactions'])->name('transactions');
    Route::get('/withdrawals', [\App\Http\Controllers\FinancialController::class, 'withdrawals'])->name('withdrawals');
    Route::post('/withdrawals', [\App\Http\Controllers\FinancialController::class, 'requestWithdrawal'])->name('withdrawals.store');
    Route::get('/payout-methods', [\App\Http\Controllers\PayoutMethodController::class, 'index'])->name('payout-methods.index');
    Route::post('/payout-methods', [\App\Http\Controllers\PayoutMethodController::class, 'store'])->name('payout-methods.store');
    Route::patch('/payout-methods/{payout_method}', [\App\Http\Controllers\PayoutMethodController::class, 'update'])->name('payout-methods.update');
    Route::delete('/payout-methods/{payout_method}', [\App\Http\Controllers\PayoutMethodController::class, 'destroy'])->name('payout-methods.destroy');

    Route::get('/add-balance', [\App\Http\Controllers\FinancialController::class, 'addBalance'])->name('add-balance');
    Route::post('/add-balance/kashier', [\App\Http\Controllers\FinancialController::class, 'depositKashier'])->name('add-balance.kashier');
    Route::get('/add-balance/success', [\App\Http\Controllers\FinancialController::class, 'success'])->name('add-balance.success');
    Route::get('/add-balance/failure', [\App\Http\Controllers\FinancialController::class, 'failure'])->name('add-balance.failure');

    // P2P Wallet Transfer Routes
    Route::get('/transfer', [\App\Http\Controllers\WalletTransferController::class, 'create'])->name('transfer.create');
    Route::post('/transfer', [\App\Http\Controllers\WalletTransferController::class, 'store'])->name('transfer.store');
    Route::get('/transfer/history', [\App\Http\Controllers\WalletTransferController::class, 'history'])->name('transfer.history');
    Route::get('/transfer/{id}', [\App\Http\Controllers\WalletTransferController::class, 'show'])->name('transfer.show');

    // P2P Wallet Transfer Live APIs
    Route::get('/transfer-api/calculate-fee', [\App\Http\Controllers\WalletTransferController::class, 'calculateFee'])->name('transfer.calculate-fee');
    Route::get('/transfer-api/search-users', [\App\Http\Controllers\WalletTransferController::class, 'searchUsers'])->name('transfer.search-users');
});

// Kashier Webhook (No Auth required)
Route::post('/financial/add-balance/webhook', [\App\Http\Controllers\FinancialController::class, 'webhook'])->name('financial.add-balance.webhook');
Route::post('/freelance/point-purchases/webhook', [\Modules\Freelance\Http\Controllers\PointPurchaseController::class, 'webhook'])->name('freelance.point-purchases.webhook');
Route::post('/subscriptions/kashier/webhook', [\App\Http\Controllers\SubscriptionController::class, 'webhook'])->name('subscriptions.kashier.webhook');

// General Messages Route
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/messages', [\App\Http\Controllers\MessagesController::class, 'index'])->name('messages.index');
    Route::post('/messages/direct', [\App\Http\Controllers\MessagesController::class, 'storeDirectMessage'])->name('messages.direct.store');
});

// Global Search
Route::middleware(['auth', 'verified'])->get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

// Chat API Routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/conversations/{id}', [\Modules\Core\Http\Controllers\ConversationController::class, 'show']);
    Route::get('/conversations/{id}/messages', [\Modules\Core\Http\Controllers\ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/read', [\Modules\Core\Http\Controllers\ConversationController::class, 'markAsRead']);
    Route::post('/conversations/{id}/messages', [\Modules\Core\Http\Controllers\MessageController::class, 'store']);

    // Admin Notes
    Route::get('/admin-notes', [\Modules\Core\Http\Controllers\AdminNoteController::class, 'index']);
    Route::post('/admin-notes', [\Modules\Core\Http\Controllers\AdminNoteController::class, 'store']);
    Route::patch('/admin-notes/{note}/pin', [\Modules\Core\Http\Controllers\AdminNoteController::class, 'togglePin']);
    Route::delete('/admin-notes/{note}', [\Modules\Core\Http\Controllers\AdminNoteController::class, 'destroy']);
});

// New API routes for polling
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/conversations', [\Modules\Core\Http\Controllers\ConversationController::class, 'index']);
    Route::get('/timer/{id}', [\App\Http\Controllers\TimerController::class, 'show']);
});
