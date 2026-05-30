<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index']);

Route::get('/blog/{slug}', [\App\Http\Controllers\BlogController::class, 'show'])->name('blog.show');

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
    Route::get('/onboarding-wizard/cities/{countryName}', [\App\Http\Controllers\OnboardingController::class, 'getCities'])->name('onboarding.cities');

    // Referral System
    Route::get('/referrals', [\App\Http\Controllers\ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/earnings', [\App\Http\Controllers\ReferralController::class, 'earns'])->name('referrals.earns');
    Route::get('/referrals/registers', [\App\Http\Controllers\ReferralController::class, 'registers'])->name('referrals.registers');
    Route::post('/referrals', [\App\Http\Controllers\ReferralController::class, 'store_referral'])->name('referrals.store');
    Route::post('/referrals/update-slug', [\App\Http\Controllers\ReferralController::class, 'update_slug'])->name('referrals.update_slug');
    Route::post('/referrals/activate', [\App\Http\Controllers\ReferralController::class, 'activate_ref'])->name('referrals.activate');
    Route::post('/referrals/reward', [\App\Http\Controllers\ReferralController::class, 'reward'])->name('referrals.reward');
});

// Public Referral Redirect Route
Route::get('/r/{ref}', [\App\Http\Controllers\ReferralController::class, 'referral_redirect'])->name('ref');

Route::get('/fix-cities', function() {
    \Illuminate\Support\Facades\DB::table('cities')->truncate();
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CitySeeder', '--force' => true]);
    return 'Done';
});

// Team Auth Routes
Route::middleware(['web'])->group(function () {
    Route::get('erp/team/login', [\Modules\ERP\Http\Controllers\Team\TeamAuthController::class, 'showLogin'])->name('erp.team.login');
    Route::post('erp/team/login', [\Modules\ERP\Http\Controllers\Team\TeamAuthController::class, 'login'])->name('erp.team.login.store');
    Route::post('erp/team/logout', [\Modules\ERP\Http\Controllers\Team\TeamAuthController::class, 'logout'])->name('erp.team.logout');
    
    // CRM Portal Alias for Telesales and CRM employees
    Route::get('crm/portal/login', [\Modules\ERP\Http\Controllers\Team\TeamAuthController::class, 'showLogin'])->name('crm.team.login');
    Route::post('crm/portal/login', [\Modules\ERP\Http\Controllers\Team\TeamAuthController::class, 'login'])->name('crm.team.login.store');
    Route::post('crm/portal/logout', [\Modules\ERP\Http\Controllers\Team\TeamAuthController::class, 'logout'])->name('crm.team.logout');
});

/* // ERP Routes (Migrated to Modules/ERP/routes/web.php)
Route::middleware(['auth', 'verified', 'onboarding', 'subscription:erp', 'erp.team.permissions'])->prefix('erp')->name('erp.')->group(function () {
    // Team Member Management (Tenant Owner Only)
    Route::get('/team-members', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'index'])->name('team-members.index');
    Route::post('/team-members', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'store'])->name('team-members.store');
    Route::put('/team-members/{id}', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'update'])->name('team-members.update');
    Route::delete('/team-members/{id}', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'destroy'])->name('team-members.destroy');

    Route::get('/dashboard', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('dashboard');
    Route::post('/clients', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'storeClient'])->name('clients.store');
    Route::put('/clients/{client}', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'updateClient'])->name('clients.update');
    Route::delete('/clients/{client}', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'destroyClient'])->name('clients.destroy');
    Route::get('/clients/{client}', [\Modules\ERP\Http\Controllers\ClientController::class, 'show'])->name('clients.show');
    Route::put('/clients/{client}/status', [\Modules\ERP\Http\Controllers\ClientController::class, 'updateStatus'])->name('clients.updateStatus');
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


    // Recurring
    Route::resource('recurring', \Modules\ERP\Http\Controllers\RecurringController::class);
    Route::post('/recurring/{recurring}/pause', [\Modules\ERP\Http\Controllers\RecurringController::class, 'pause'])->name('recurring.pause');
    Route::post('/recurring/{recurring}/resume', [\Modules\ERP\Http\Controllers\RecurringController::class, 'resume'])->name('recurring.resume');
    Route::get('/recurring/{recurring}/logs', [\Modules\ERP\Http\Controllers\RecurringController::class, 'logs'])->name('recurring.logs');

    // ── ERP Task System ──────────────────────────────────────────────
    // Recovered from old project: Admin/TaskController + Admin/TodoController
    // Admin/tenant creates tasks for TenantClients and manages todo items.
    Route::get('/tasks', [\Modules\ERP\Http\Controllers\TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/as-list', [\Modules\ERP\Http\Controllers\TaskController::class, 'asList'])->name('tasks.as_list');
    Route::post('/tasks', [\Modules\ERP\Http\Controllers\TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [\Modules\ERP\Http\Controllers\TaskController::class, 'show'])->name('tasks.show');
    Route::put('/tasks/{task}', [\Modules\ERP\Http\Controllers\TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [\Modules\ERP\Http\Controllers\TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/archive', [\Modules\ERP\Http\Controllers\TaskController::class, 'archive'])->name('tasks.archive');
    Route::post('/tasks/{task}/unarchive', [\Modules\ERP\Http\Controllers\TaskController::class, 'unarchive'])->name('tasks.unarchive');
    // Todo items
    Route::post('/tasks/{task}/items', [\Modules\ERP\Http\Controllers\TaskController::class, 'storeItem'])->name('tasks.items.store');
    Route::put('/tasks/{task}/items/{item}', [\Modules\ERP\Http\Controllers\TaskController::class, 'updateItem'])->name('tasks.items.update');
    Route::post('/tasks/{task}/items/{item}/complete', [\Modules\ERP\Http\Controllers\TaskController::class, 'completeItem'])->name('tasks.items.complete');
    Route::post('/tasks/{task}/items/sort', [\Modules\ERP\Http\Controllers\TaskController::class, 'sortItems'])->name('tasks.items.sort');
    Route::post('/tasks/{task}/items/{item}/pause', [\Modules\ERP\Http\Controllers\TaskController::class, 'pauseItem'])->name('tasks.items.pause');
    Route::post('/tasks/{task}/items/{item}/resume', [\Modules\ERP\Http\Controllers\TaskController::class, 'resumeItem'])->name('tasks.items.resume');
    Route::delete('/tasks/{task}/items/{item}', [\Modules\ERP\Http\Controllers\TaskController::class, 'destroyItem'])->name('tasks.items.destroy');

    // ── ERP Client Notes ─────────────────────────────────────────────
    // Recovered from old project: Admin/UserNotesController (per-user notes)
    // Parallel system: tenant manages notes on their TenantClients.
    Route::post('/clients/{client}/notes', [\Modules\ERP\Http\Controllers\ClientNoteController::class, 'store'])->name('clients.notes.store');
    Route::delete('/clients/{client}/notes/{note}', [\Modules\ERP\Http\Controllers\ClientNoteController::class, 'destroy'])->name('clients.notes.destroy');
    Route::post('/clients/{client}/notes/{note}/archive', [\Modules\ERP\Http\Controllers\ClientNoteController::class, 'archive'])->name('clients.notes.archive');
    Route::post('/clients/{client}/notes/{note}/unarchive', [\Modules\ERP\Http\Controllers\ClientNoteController::class, 'unarchive'])->name('clients.notes.unarchive');

    // ── ERP Support Tickets ───────────────────────────────────────────
    Route::post('/tickets', [\App\Http\Controllers\SupportTicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/{ticket}/resolve', [\App\Http\Controllers\SupportTicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/tickets/{ticket}/close', [\App\Http\Controllers\SupportTicketController::class, 'close'])->name('tickets.close');
    Route::delete('/tickets/{ticket}', [\App\Http\Controllers\SupportTicketController::class, 'destroy'])->name('tickets.destroy');

    // ── ERP Workspace Notes ───────────────────────────────────────────
    // Tenant-scoped scratchpad notes for the ERP dashboard.
    Route::post('/notes', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'store'])->name('notes.store');
    Route::put('/notes/{note}', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'update'])->name('notes.update');
    Route::post('/notes/{note}/toggle-pin', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'togglePin'])->name('notes.togglePin');
    Route::delete('/notes/{note}', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'destroy'])->name('notes.destroy');
}); */

// ── Client-Facing Billing Routes ─────────────────────────────────────
// Platform users (subscribers) view their invoices issued by admin and pay them.
// These are CORE platform billing routes — NOT related to the ERP module.
Route::middleware(['auth', 'verified', 'onboarding'])->prefix('billing')->name('billing.')->group(function () {
    Route::get('/invoices', [\Modules\ERP\Http\Controllers\InvoicePaymentController::class, 'clientIndex'])->name('invoices.index');
    Route::get('/invoices/{uuid}/pay', [\Modules\ERP\Http\Controllers\InvoicePaymentController::class, 'show'])->name('invoices.pay');
    Route::post('/invoices/{uuid}/pay/wallet', [\Modules\ERP\Http\Controllers\InvoicePaymentController::class, 'processWalletPayment'])->name('invoices.pay.wallet');
});



// Marketplace Routes — literal routes BEFORE wildcards
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    // Public: browse list
    Route::get('/services', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'index'])->name('services.index');

    // Auth-only: dashboard + create wizard (MUST be before /{id} wildcard)
    Route::middleware(['auth', 'verified', 'onboarding', 'subscription:marketplace'])->group(function () {
        Route::get('/dashboard', [\Modules\Marketplace\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/services/create', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'create'])->name('services.create');
        Route::post('/services', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{service}/edit', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'edit'])->name('services.edit');
        Route::put('/services/{service}', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'update'])->name('services.update');

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

        // Reviews
        Route::post('/orders/{order}/review', [\Modules\Marketplace\Http\Controllers\ServiceReviewController::class, 'store'])->name('orders.review.store');
        Route::delete('/reviews/{review}', [\Modules\Marketplace\Http\Controllers\ServiceReviewController::class, 'destroy'])->name('reviews.destroy');
    });

    // Public: show single service — wildcard LAST
    Route::get('/services/{id}', [\Modules\Marketplace\Http\Controllers\ServiceController::class, 'show'])->name('services.show');
});

// Marketplace Admin Routes
Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->prefix('admin/marketplace')->name('admin.marketplace.')->group(function () {
    // Categories
    Route::get('/categories', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [\Modules\Marketplace\Http\Controllers\ServiceCategoryController::class, 'destroy'])->name('categories.destroy');

    // Services Admin Actions

    // Orders
    Route::get('/orders', [\App\Http\Controllers\Admin\MarketplaceOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [\App\Http\Controllers\Admin\MarketplaceOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/dispute', [\App\Http\Controllers\Admin\MarketplaceOrderController::class, 'resolveDispute'])->name('orders.dispute.resolve');

    // Admin Views
});

// Settings & Account Routes
Route::middleware(['auth', 'verified'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/backup', [\App\Http\Controllers\TenantBackupController::class, 'index'])->name('backup.index');
    Route::get('/backup/export', [\App\Http\Controllers\TenantBackupController::class, 'export'])->name('backup.export');
    Route::post('/backup/import', [\App\Http\Controllers\TenantBackupController::class, 'import'])->name('backup.import');
});

if (file_exists(base_path('Modules/CRM/routes/web.php'))) {
    require base_path('Modules/CRM/routes/web.php');
}

// Admin Routes
Route::middleware(['auth', 'verified', 'onboarding', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');

    // Clients (thin ERP-linked view) - removed as per request

    // KYC Review
    Route::get('/kyc', [\App\Http\Controllers\Admin\KycController::class, 'index'])->name('kyc.index');
    Route::post('/kyc/{id}/approve', [\App\Http\Controllers\Admin\KycController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{id}/reject', [\App\Http\Controllers\Admin\KycController::class, 'reject'])->name('kyc.reject');

    // ── Admin Blog Articles ─────────────────────────────────────────
    Route::resource('/blog-articles', \App\Http\Controllers\Admin\AdminBlogArticleController::class)->only(['index']);

    // ── Admin Employee Todos ────────────────────────────────────────

    // ── Admin Projects ──────────────────────────────────────────────
    Route::resource('/projects', \App\Http\Controllers\Admin\ProjectController::class)->except(['create', 'edit', 'show']);
    Route::post('/projects/{project}/archive', [\App\Http\Controllers\Admin\ProjectController::class, 'archive'])->name('projects.archive');
    Route::post('/projects/{project}/restore', [\App\Http\Controllers\Admin\ProjectController::class, 'restore'])->name('projects.restore');

    // ── Admin Plans ───────────────────────────────────────────────
    Route::resource('/plans', \App\Http\Controllers\Admin\PlanController::class)->except(['create', 'edit', 'show']);

    // ── Admin Invoices (Platform Billing) ─────────────────────────
    Route::get('/invoices', [\App\Http\Controllers\Admin\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/unpaid', [\App\Http\Controllers\Admin\InvoiceController::class, 'unpaid'])->name('invoices.unpaid');
    Route::get('/invoices/archive', [\App\Http\Controllers\Admin\InvoiceController::class, 'archive'])->name('invoices.archive');
    Route::post('/invoices/bulk-action', [\App\Http\Controllers\Admin\InvoiceController::class, 'bulkAction'])->name('invoices.bulk-action');
    Route::get('/invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'show'])->name('invoices.show');
    Route::put('/invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('/invoices/{invoice}/mark-paid', [\App\Http\Controllers\Admin\InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{invoice}/cancel', [\App\Http\Controllers\Admin\InvoiceController::class, 'cancel'])->name('invoices.cancel');







    // ── Platform Users Transactions ───────────────────────────────
    Route::get('/transactions', [\App\Http\Controllers\Admin\AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [\App\Http\Controllers\Admin\AdminTransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [\App\Http\Controllers\Admin\AdminTransactionController::class, 'store'])->name('transactions.store');

    // ── Admin Financial Operations ────────────────────────────────
    Route::get('/finance', [\App\Http\Controllers\Admin\FinancialOperationsController::class, 'index'])->name('finance.index');
    Route::post('/finance', [\App\Http\Controllers\Admin\FinancialOperationsController::class, 'store'])->name('finance.store');
    Route::put('/finance/{entry}', [\App\Http\Controllers\Admin\FinancialOperationsController::class, 'update'])->name('finance.update');
    Route::delete('/finance/{entry}', [\App\Http\Controllers\Admin\FinancialOperationsController::class, 'destroy'])->name('finance.destroy');
    Route::post('/finance/{entry}/mark-paid', [\App\Http\Controllers\Admin\FinancialOperationsController::class, 'markAsPaid'])->name('finance.mark-paid');

    // Recurring Business Operations (Recovered from legacy project)
    Route::prefix('business/recurring')->group(function () {
        // Costs
        Route::get('costs', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_costs'])->name('recurring_costs.index');
        Route::get('costs/create', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'create_recurring_costs'])->name('recurring_costs.create');
        Route::post('costs', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'store_recurring_costs'])->name('recurring_costs.store');
        Route::get('costs/edit/{id}', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'edit_recurring_costs'])->name('recurring_costs.edit');
        Route::put('costs/{id}', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'update_recurring_costs'])->name('recurring_costs.update');
        Route::get('costs/{id}', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_costs_view'])->name('recurring_costs.view');
        Route::delete('costs/{id}/delete', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_costs_delete'])->name('recurring_costs.delete');
        Route::delete('costs/{id}/delete-with-transaction', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_costs_delete_with_transaction'])->name('recurring_costs.delete_with_transaction');

        // Income
        Route::get('income', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_income'])->name('recurring_income.index');
        Route::post('income', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'store_recurring_income'])->name('recurring_income.store');
        Route::get('income/edit/{id}', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'edit_recurring_income'])->name('recurring_income.edit');
        Route::put('income/{id}', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'update_recurring_income'])->name('recurring_income.update');
        Route::get('income/{id}', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_income_view'])->name('recurring_income.view');
        Route::delete('income/{id}/delete', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_income_delete'])->name('recurring_income.delete');
        Route::delete('income/{id}/delete-with-transaction', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_income_delete_with_transaction'])->name('recurring_income.delete_with_transaction');

        // Salaries
        Route::get('salaries', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_salaries'])->name('recurring_salaries.index');
        Route::post('salaries', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'store_recurring_salaries'])->name('recurring_salaries.store');
        Route::get('salaries/edit/{id}', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'edit_recurring_salaries'])->name('recurring_salaries.edit');
        Route::put('salaries/{id}', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'update_recurring_salaries'])->name('recurring_salaries.update');
        Route::get('salaries/{id}', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_salaries_view'])->name('recurring_salaries.view');
        Route::delete('salaries/{id}/delete', [\App\Http\Controllers\Admin\RecurringBusinessController::class, 'recurring_salaries_delete'])->name('recurring_salaries.delete');
    });

    // ── Admin Hours Calendar ──────────────────────────────────────
    Route::get('/hours-calendar', [\App\Http\Controllers\Admin\HoursCalendarController::class, 'index'])->name('hours-calendar.index');
    Route::post('/hours-calendar/data', [\App\Http\Controllers\Admin\HoursCalendarController::class, 'getData'])->name('hours-calendar.data');

    // ── Admin Busy Times ──────────────────────────────────────────────
    Route::get('/busy-times', [\App\Http\Controllers\Admin\AdminBusyTimesController::class, 'index'])->name('busy-times.index');
    Route::post('/busy-times/{busyTime}/toggle-active', [\App\Http\Controllers\Admin\AdminBusyTimesController::class, 'toggleActive'])->name('busy-times.toggle-active');
    Route::delete('/busy-times/{busyTime}', [\App\Http\Controllers\Admin\AdminBusyTimesController::class, 'destroy'])->name('busy-times.destroy');

    // ── Admin Phase 3 (Support & Communication) ───────────────────
    Route::resource('tickets', \App\Http\Controllers\Admin\AdminTicketController::class)->only(['index', 'show', 'update']);
    Route::post('tickets/{ticket}/reply', [\App\Http\Controllers\Admin\AdminTicketController::class, 'reply'])->name('tickets.reply');
    
    Route::resource('vouchers', \App\Http\Controllers\Admin\AdminVoucherController::class);

    Route::resource('coupons', \App\Http\Controllers\Admin\AdminCouponController::class);

    Route::resource('payment-methods', \App\Http\Controllers\Admin\AdminPaymentMethodController::class)->only(['index', 'show', 'update']);

    Route::resource('withdraw-requests', \App\Http\Controllers\Admin\AdminWithdrawRequestController::class)->only(['index', 'show', 'update']);
    
    // ── Admin Phase 4 (Settings & Localization) ───────────────────
    Route::get('settings', [\App\Http\Controllers\Admin\AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\Admin\AdminSettingController::class, 'store'])->name('settings.store');
    
    Route::resource('language-lines', \App\Http\Controllers\Admin\AdminLanguageLineController::class)->except(['create', 'show', 'edit']);
    Route::post('language-lines/auto-translate', [\App\Http\Controllers\Admin\AdminLanguageLineController::class, 'autoTranslate'])->name('language-lines.auto-translate');
    Route::post('language-lines/import', [\App\Http\Controllers\Admin\AdminLanguageLineController::class, 'import'])->name('language-lines.import');
    
    Route::get('quotations/{contract}/print', [\App\Http\Controllers\Admin\AdminQuotationController::class, 'print'])->name('quotations.print');
    
    Route::resource('free-downloads', \App\Http\Controllers\Admin\AdminFreeDownloadController::class)->except(['create', 'show', 'edit']);
    


    // ── Freelance (Admin Control) ───────────────────────────────────
    Route::prefix('freelance')->name('freelance.')->group(function () {
        Route::post('skills/{skill}/approve', [\App\Http\Controllers\Admin\FreelanceSkillController::class, 'approve'])->name('skills.approve');
        Route::post('skills/{skill}/reject', [\App\Http\Controllers\Admin\FreelanceSkillController::class, 'reject'])->name('skills.reject');
        Route::post('skills/block-user/{user}', [\App\Http\Controllers\Admin\FreelanceSkillController::class, 'blockUser'])->name('skills.block-user');
        Route::resource('skills', \App\Http\Controllers\Admin\FreelanceSkillController::class)->except(['show']);
        Route::resource('jobs', \App\Http\Controllers\Admin\FreelanceJobController::class)->only(['index', 'show', 'destroy']);
        Route::post('jobs/{job}/status', [\App\Http\Controllers\Admin\FreelanceJobController::class, 'updateStatus'])->name('jobs.status');
        Route::resource('contracts', \App\Http\Controllers\Admin\FreelanceContractController::class)->only(['index', 'show', 'destroy']);
        Route::post('contracts/{contract}/status', [\App\Http\Controllers\Admin\FreelanceContractController::class, 'updateStatus'])->name('contracts.status');
    });



    // ── User Management (Full Admin Control) ────────────────────────
    // Recovered from old project: Admin/UsersController
    Route::get('/users', [\App\Http\Controllers\Admin\UsersController::class, 'index'])->name('users.index');
    Route::get('/users/create', [\App\Http\Controllers\Admin\UsersController::class, 'create'])->name('users.create');
    Route::post('/users', [\App\Http\Controllers\Admin\UsersController::class, 'store'])->name('users.store');
    Route::get('/users/problematic', [\App\Http\Controllers\Admin\UsersController::class, 'problematic'])->name('users.problematic');
    Route::get('/users/co-work', [\App\Http\Controllers\Admin\UsersController::class, 'coWork'])->name('users.co-work');
    Route::get('/users/earning-analyze', [\App\Http\Controllers\Admin\UsersController::class, 'earningAnalyze'])->name('users.earning-analyze');
    
    // Matches exact old links
    Route::get('/users/{id}/login-as', [\App\Http\Controllers\Admin\UsersController::class, 'loginAs'])->name('users.login-as');
    Route::post('/users/{id}/login-as', [\App\Http\Controllers\Admin\UsersController::class, 'loginAs']); // Allows POST for React compatibility
    Route::get('/users/reset-password/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'reset_password'])->name('users.reset-password');
    Route::post('/users/{id}/generate-password', [\App\Http\Controllers\Admin\UsersController::class, 'reset_password'])->name('users.generate-password');
    Route::get('/users/{id}/referrals', [\App\Http\Controllers\Admin\UsersController::class, 'referrals'])->name('users.referrals');
    Route::delete('/users/{user}/referrals/{referred_user}/unlink', [\App\Http\Controllers\Admin\UsersController::class, 'unlink_referral'])->name('users.referrals.unlink');
    Route::get('/users/files/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'files'])->name('users.files');
    Route::get('/users/reports/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'reports'])->name('users.reports');
    Route::get('/users/{id}/tasks/add', [\App\Http\Controllers\Admin\UsersController::class, 'add_task'])->name('users.tasks.add');
    
    Route::get('/users/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'show'])->name('users.show');
    Route::get('/users/{id}/edit', [\App\Http\Controllers\Admin\UsersController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/toggle-block', [\App\Http\Controllers\Admin\UsersController::class, 'toggleBlock'])->name('users.toggleBlock');
    Route::post('/users/{id}/membership', [\App\Http\Controllers\Admin\UsersController::class, 'activateMembership'])->name('users.membership.activate');
    Route::put('/users/{id}/membership/{sub_id}', [\App\Http\Controllers\Admin\UsersController::class, 'updateMembership'])->name('users.membership.update');
    Route::delete('/users/{id}/membership/{sub_id}', [\App\Http\Controllers\Admin\UsersController::class, 'deleteMembership'])->name('users.membership.delete');

    // ── Points Control ───────────────────────────────────────────────
    Route::get('/points_controller', [\App\Http\Controllers\Admin\AdminPointsController::class, 'index'])->name('points.index');
    Route::post('/points_controller/{user}/adjust', [\App\Http\Controllers\Admin\AdminPointsController::class, 'adjustPoints'])->name('points.adjust');
    Route::get('/points_controller/{user}/history', [\App\Http\Controllers\Admin\AdminPointsController::class, 'history'])->name('points.history');

    // ── Charity Counter ──────────────────────────────────────────────
    Route::get('/charity-counter', [\App\Http\Controllers\Admin\CharityCounterController::class, 'index'])->name('charity-counter.index');
    Route::post('/charity-counter/add-amount', [\App\Http\Controllers\Admin\CharityCounterController::class, 'addAmount'])->name('charity-counter.add-amount');
    Route::post('/charity-counter/subtract-amount', [\App\Http\Controllers\Admin\CharityCounterController::class, 'subtractAmount'])->name('charity-counter.subtract-amount');

    // ── User Notes ───────────────────────────────────────────────────
    // Recovered from old project: Admin/UserNotesController
    Route::get('/users/{userId}/notes/json', [\App\Http\Controllers\Admin\UserNoteController::class, 'indexJson'])->name('users.notes.json');
    Route::get('/users/{userId}/notes', [\App\Http\Controllers\Admin\UserNoteController::class, 'index'])->name('users.notes.index');
    Route::post('/users/{userId}/notes', [\App\Http\Controllers\Admin\UserNoteController::class, 'store'])->name('users.notes.store');
    Route::delete('/users/{userId}/notes/{noteId}', [\App\Http\Controllers\Admin\UserNoteController::class, 'destroy'])->name('users.notes.destroy');
    Route::post('/users/{userId}/notes/{noteId}/archive', [\App\Http\Controllers\Admin\UserNoteController::class, 'archive'])->name('users.notes.archive');
    Route::post('/users/{userId}/notes/{noteId}/unarchive', [\App\Http\Controllers\Admin\UserNoteController::class, 'unarchive'])->name('users.notes.unarchive');

    // ── User Files ───────────────────────────────────────────────────
    // Recovered from old project: Admin/FileController
    Route::get('/users/{userId}/files', [\App\Http\Controllers\Admin\UserFileController::class, 'index'])->name('users.files.index');
    Route::post('/users/{userId}/files/upload', [\App\Http\Controllers\Admin\UserFileController::class, 'upload'])->name('users.files.upload');
    Route::post('/users/{userId}/files/folder', [\App\Http\Controllers\Admin\UserFileController::class, 'newFolder'])->name('users.files.folder');
    Route::get('/users/{userId}/files/download', [\App\Http\Controllers\Admin\UserFileController::class, 'download'])->name('users.files.download');
    Route::post('/users/{userId}/files/rename', [\App\Http\Controllers\Admin\UserFileController::class, 'rename'])->name('users.files.rename');
    Route::post('/users/{userId}/files/move', [\App\Http\Controllers\Admin\UserFileController::class, 'move'])->name('users.files.move');
    Route::delete('/users/{userId}/files', [\App\Http\Controllers\Admin\UserFileController::class, 'delete'])->name('users.files.delete');

    // ARCHITECTURE NOTE:
    // Admin does NOT have separate invoice or task panels.
    // The ERP system IS the admin's tool for managing platform users (their "clients").
    // Admin uses Login-As (/admin/users/{id}/login-as) to enter a user's ERP context
    // and access their invoices, tasks, projects, etc. from within the ERP workspace.
    // ERP routes live at /erp/* and are available to any authenticated+subscribed user.

    // ── Serial License System ─────────────────────────────────────────
    // Copied from old project. Fully internal — admin only.
    // API check-in lives in routes/api.php (no auth, throttled).

    // Software registry (auto-created by API, admin manages default_status)
    Route::get('/serial-softwares', [\App\Http\Controllers\Admin\SerialSoftwareController::class, 'index'])->name('serial-softwares.index');
    Route::post('/serial-softwares', [\App\Http\Controllers\Admin\SerialSoftwareController::class, 'store'])->name('serial-softwares.store');
    Route::patch('/serial-softwares/{serialSoftware}/status', [\App\Http\Controllers\Admin\SerialSoftwareController::class, 'updateStatus'])->name('serial-softwares.status');
    Route::delete('/serial-softwares/{serialSoftware}', [\App\Http\Controllers\Admin\SerialSoftwareController::class, 'destroy'])->name('serial-softwares.destroy');

    // Device registry (auto-created by API check-in, admin manages status)
    Route::get('/serial-devices', [\App\Http\Controllers\Admin\SerialDeviceController::class, 'index'])->name('serial-devices.index');
    Route::patch('/serial-devices/{serialDevice}/status', [\App\Http\Controllers\Admin\SerialDeviceController::class, 'updateStatus'])->name('serial-devices.status');
    Route::delete('/serial-devices/{serialDevice}', [\App\Http\Controllers\Admin\SerialDeviceController::class, 'destroy'])->name('serial-devices.destroy');

    // User-Device assignments (admin maps device → user)
    Route::get('/serial-user-devices', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'index'])->name('serial-user-devices.index');
    Route::get('/serial-user-devices/by-user', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'byUser'])->name('serial-user-devices.by-user');
    Route::get('/serial-user-devices/assign', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'assign'])->name('serial-user-devices.assign');
    Route::post('/serial-user-devices', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'store'])->name('serial-user-devices.store');
    Route::patch('/serial-user-devices/{serialUserDevice}/status', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'updateStatus'])->name('serial-user-devices.status');
    Route::patch('/serial-user-devices/users/{user}/status', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'updateUserStatus'])->name('serial-user-devices.update-user-status');
    Route::patch('/serial-user-devices/users/{user}/temp-valid', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'updateUserTempValid'])->name('serial-user-devices.update-user-temp-valid');
    Route::delete('/serial-user-devices/{serialUserDevice}', [\App\Http\Controllers\Admin\SerialUserDeviceController::class, 'destroy'])->name('serial-user-devices.destroy');

    // ── Tools Marketplace Admin ───────────────────────────────────────
    // Admin manages the tool catalog, releases, and pricing plans.
    Route::prefix('tools')->name('tools.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\Tools\AdminToolController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\Tools\AdminToolController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\Tools\AdminToolController::class, 'store'])->name('store');
        Route::get('/{tool}/edit', [\App\Http\Controllers\Admin\Tools\AdminToolController::class, 'edit'])->name('edit');
        Route::put('/{tool}', [\App\Http\Controllers\Admin\Tools\AdminToolController::class, 'update'])->name('update');
        Route::delete('/{tool}', [\App\Http\Controllers\Admin\Tools\AdminToolController::class, 'destroy'])->name('destroy');
        Route::post('/{tool}/upload-version', [\App\Http\Controllers\Admin\Tools\AdminToolController::class, 'uploadVersion'])->name('upload-version');
    });

    // ── Reseller Management ────────────────────────────────────────────────────
    Route::prefix('resellers')->name('resellers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'store'])->name('store');
        Route::get('/search-users', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'searchUsers'])->name('search-users');
        Route::get('/{id}', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'show'])->name('show');
        Route::put('/{id}', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/balance', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'adjustBalance'])->name('balance');
        Route::post('/{resellerId}/users/{userId}/suspend', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'suspendUser'])->name('users.suspend');
        Route::post('/{resellerId}/users/{userId}/activate', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'activateUser'])->name('users.activate');
        Route::post('/{resellerId}/users/{userId}/clear-flag', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'clearSharingFlag'])->name('users.clear-flag');
        Route::post('/{resellerId}/users/{userId}/toggle-check', [\App\Http\Controllers\Admin\Tools\AdminResellerController::class, 'toggleSharingCheck'])->name('users.toggle-check');
    });

    // ERP Oversight Admin Routes
    Route::prefix('erp')->name('erp.')->group(function () {
        Route::get('/', [\Modules\ERP\Http\Controllers\Admin\ERPAdminController::class, 'index'])->name('index');
        Route::get('/{id}', [\Modules\ERP\Http\Controllers\Admin\ERPAdminController::class, 'show'])->name('show');
    });

    // Admin Tasks List (platform checklist items)
    Route::get('/tasks/as_list', [\App\Http\Controllers\Admin\AdminTaskController::class, 'asList'])->name('tasks.as_list');
    Route::post('/tasks/todos/{todo}/complete', [\App\Http\Controllers\Admin\AdminTaskController::class, 'completeTodo'])->name('tasks.todos.complete');
    Route::get('/tasks/calendar', [\App\Http\Controllers\Admin\AdminTaskController::class, 'calendar'])->name('tasks.calendar');
    Route::get('/tasks/client-tasks', [\App\Http\Controllers\Admin\AdminTaskController::class, 'clientTasks'])->name('tasks.client-tasks');
    Route::post('/tasks/client-tasks/{client}/todos', [\App\Http\Controllers\Admin\AdminTaskController::class, 'storeClientTodo'])->name('tasks.client-tasks.store');
    Route::post('/tasks/todos/{todo}/refund', [\App\Http\Controllers\Admin\AdminTaskController::class, 'refundTodo'])->name('tasks.todos.refund');
    Route::post('/tasks/todos/{todo}/schedule', [\App\Http\Controllers\Admin\AdminTaskController::class, 'scheduleTodo'])->name('tasks.todos.schedule');

    Route::get('/erp/{id}/impersonate', [\App\Http\Controllers\ImpersonateController::class, 'impersonate'])->name('erp.impersonate');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/stop-impersonate', [\App\Http\Controllers\ImpersonateController::class, 'stopImpersonating'])->name('admin.stop-impersonate');
});

// ── Reseller Portal (public, iframe-embeddable) ───────────────────────────────
// No auth middleware here — the portal itself decides whether to show login or tools.
// X-Frame-Options is set to ALLOWALL for this route via a response header.
Route::get('/reseller/{token}', [\Modules\Tools\Http\Controllers\ResellerPortalController::class, 'show'])
    ->name('reseller.portal')
    ->withoutMiddleware([\Illuminate\Http\Middleware\FrameGuard::class]);

// CRM Lead Capture Iframe Routes (No Auth required, allowed to be embedded)
Route::get('/crm/embed/capture/{token}', [\Modules\CRM\Http\Controllers\LeadCaptureController::class, 'show'])
    ->name('crm.embed.capture.show')
    ->withoutMiddleware([\Illuminate\Http\Middleware\FrameGuard::class]);
Route::post('/crm/embed/capture/{token}', [\Modules\CRM\Http\Controllers\LeadCaptureController::class, 'store'])
    ->name('crm.embed.capture.store')
    ->withoutMiddleware([\Illuminate\Http\Middleware\FrameGuard::class]);


// SaaS Subscription & Billing Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Points System
    Route::get('/points', [\App\Http\Controllers\PointPurchaseController::class, 'index'])->name('points.index');
    Route::post('/points/purchase', [\App\Http\Controllers\PointPurchaseController::class, 'store'])->name('point-purchases.store');
    Route::post('/points/purchase-wallet', [\App\Http\Controllers\PointPurchaseController::class, 'storeWallet'])->name('point-purchases.store-wallet');
    
    // Contracts (Generated from Proposals or Wizard)
    Route::get('/contracts', [\App\Http\Controllers\iSaaS\ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/create', [\App\Http\Controllers\iSaaS\ContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts', [\App\Http\Controllers\iSaaS\ContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{contract}/edit', [\App\Http\Controllers\iSaaS\ContractController::class, 'edit'])->name('contracts.edit');
    Route::put('/contracts/{contract}', [\App\Http\Controllers\iSaaS\ContractController::class, 'update'])->name('contracts.update');
    Route::delete('/contracts/{contract}', [\App\Http\Controllers\iSaaS\ContractController::class, 'destroy'])->name('contracts.destroy');
    Route::post('/contracts/{contract}/status', [\App\Http\Controllers\iSaaS\ContractController::class, 'updateStatus'])->name('contracts.update-status');
    Route::post('/contracts/ai-generate', [\App\Http\Controllers\iSaaS\ContractController::class, 'aiGenerate'])->name('contracts.ai-generate');
    Route::post('/contracts/ai-review', [\App\Http\Controllers\iSaaS\ContractController::class, 'aiReview'])->name('contracts.ai-review');

    Route::post('/freelance/points/purchase', [\App\Http\Controllers\PointPurchaseController::class, 'store'])->name('freelance.point-purchases.store');
    Route::post('/freelance/points/purchase-wallet', [\App\Http\Controllers\PointPurchaseController::class, 'storeWallet'])->name('freelance.point-purchases.store-wallet');

    Route::get('/subscriptions/plans', [\App\Http\Controllers\SubscriptionController::class, 'plans'])->name('subscriptions.plans');
    Route::post('/subscriptions/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::post('/subscriptions/subscribe-custom', [\App\Http\Controllers\SubscriptionController::class, 'subscribeCustom'])->name('subscriptions.subscribe-custom');
    Route::post('/subscriptions/trial', [\App\Http\Controllers\SubscriptionController::class, 'startTrial'])->name('subscriptions.trial');
    Route::post('/subscriptions/calculate-custom', [\App\Http\Controllers\SubscriptionController::class, 'calculateCustomPrice'])->name('subscriptions.calculate-custom');
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
     // Route::get('/conversations/{id}', [\App\Http\Controllers\ConversationController::class, 'show']);
    // Route::get('/conversations/{id}/messages', [\App\Http\Controllers\ConversationController::class, 'messages']);
    // Route::post('/conversations/{id}/read', [\App\Http\Controllers\ConversationController::class, 'markAsRead']);

    Route::get('/messages', function () {
        return Inertia::render('Messages/Index');
    })->name('messages.index');

    Route::get('/notifications', function () {
        return Inertia::render('Notifications/Index');
    })->name('notifications.index');

    // iSAAS Connected Apps Routes
    Route::prefix('isaas')->name('isaas.')->group(function () {
        // Project Proposals (AI Estimator)
        Route::get('/proposals', [\App\Http\Controllers\Isaas\ProjectProposalController::class, 'index'])->name('proposals.index');
        Route::get('/proposals/create', [\App\Http\Controllers\Isaas\ProjectProposalController::class, 'create'])->name('proposals.create');
        Route::post('/proposals/estimate', [\App\Http\Controllers\Isaas\ProjectProposalController::class, 'estimate'])->name('proposals.estimate');
        Route::get('/proposals/{id}', [\App\Http\Controllers\Isaas\ProjectProposalController::class, 'show'])->name('proposals.show');
        Route::put('/proposals/{id}', [\App\Http\Controllers\Isaas\ProjectProposalController::class, 'update'])->name('proposals.update');
        Route::post('/proposals/{id}/convert', [\App\Http\Controllers\Isaas\ProjectProposalController::class, 'convert'])->name('proposals.convert');
        Route::delete('/proposals/{id}', [\App\Http\Controllers\Isaas\ProjectProposalController::class, 'destroy'])->name('proposals.destroy');
    });

    // Route::get('/conversations', [\App\Http\Controllers\ConversationController::class, 'index']);
    Route::get('/transfer-api/calculate-fee', [\App\Http\Controllers\WalletTransferController::class, 'calculateFee'])->name('transfer.calculate-fee');
    Route::get('/transfer-api/search-users', [\App\Http\Controllers\WalletTransferController::class, 'searchUsers'])->name('transfer.search-users');
    Route::post('/transfer', [\App\Http\Controllers\WalletTransferController::class, 'store'])->name('transfer.store');
    Route::get('/transfer/history', [\App\Http\Controllers\WalletTransferController::class, 'history'])->name('transfer.history');
    Route::get('/transfer/{id}', [\App\Http\Controllers\WalletTransferController::class, 'show'])->name('transfer.show');
});

// Chat API Routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/conversations/{id}/messages', [\App\Http\Controllers\ConversationController::class, 'messages']);
    Route::post('/conversations/{id}/read', [\App\Http\Controllers\ConversationController::class, 'markAsRead']);
    Route::post('/conversations/{id}/messages', [\App\Http\Controllers\ConversationController::class, 'storeMessage']);
});

// Kashier Webhook (No Auth required)
Route::post('/financial/add-balance/webhook', [\App\Http\Controllers\FinancialController::class, 'webhook'])->name('financial.add-balance.webhook');
Route::post('/freelance/point-purchases/webhook', [\App\Http\Controllers\PointPurchaseController::class, 'webhook'])->name('freelance.point-purchases.webhook');
Route::post('/subscriptions/kashier/webhook', [\App\Http\Controllers\SubscriptionController::class, 'webhook'])->name('subscriptions.kashier.webhook');

// ── Public Client Portal (No Auth Required) ──
Route::prefix('c')->name('client-portal.')->group(function () {
    Route::get('/contracts/{uuid}', [\App\Http\Controllers\iSaaS\ClientPortalController::class, 'showContract'])->name('contracts.show');
    Route::post('/contracts/{uuid}/sign', [\App\Http\Controllers\iSaaS\ClientPortalController::class, 'signContract'])->name('contracts.sign');
});

// General Messages Route
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/messages', [\App\Http\Controllers\MessagesController::class, 'index'])->name('messages.index');
    Route::post('/messages/direct', [\App\Http\Controllers\MessagesController::class, 'storeDirectMessage'])->name('messages.direct.store');
});

// Global Search
Route::middleware(['auth', 'verified'])->get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

// Chat API Routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    // Route::get('/conversations/{id}', [\App\Http\Controllers\ConversationController::class, 'show']);
    // Route::get('/conversations/{id}/messages', [\App\Http\Controllers\ConversationController::class, 'messages']);
    // Route::post('/conversations/{id}/read', [\App\Http\Controllers\ConversationController::class, 'markAsRead']);
    // Route::post('/conversations/{id}/messages', [\App\Http\Controllers\MessageController::class, 'store']);

    // Admin Notes
    // Route::get('/admin-notes', [\App\Http\Controllers\AdminNoteController::class, 'index']);
    // Route::post('/admin-notes', [\App\Http\Controllers\AdminNoteController::class, 'store']);
    // Route::patch('/admin-notes/{note}/pin', [\App\Http\Controllers\AdminNoteController::class, 'togglePin']);
    // Route::delete('/admin-notes/{note}', [\App\Http\Controllers\AdminNoteController::class, 'destroy']);
});

// New API routes for polling
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    // Route::get('/conversations', [\App\Http\Controllers\ConversationController::class, 'index']);
    Route::get('/timer/{id}', [\App\Http\Controllers\TimerController::class, 'show']);

    // Activity Engine — widget API for dashboard feeds
    // Route::get('/activity', [\App\Http\Controllers\ActivityController::class, 'feed'])->name('api.activity.feed');
});

// ── Activity Engine ───────────────────────────────────────────────────────────
// The heartbeat of the iSAAS ecosystem. Full-page operational activity log.
Route::middleware(['auth', 'verified'])->group(function () {
    // Route::get('/activity', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activity.index');
});

