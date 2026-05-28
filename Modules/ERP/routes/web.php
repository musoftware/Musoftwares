<?php

use Illuminate\Support\Facades\Route;
use Modules\ERP\Http\Controllers\InvoiceController;

Route::middleware(['web', 'auth', 'tenant.active'])
    ->prefix('erp')
    ->name('erp.')
    ->group(function () {
        // ── Dashboard & Onboarding ──
        Route::get('dashboard', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('dashboard');
        Route::get('onboarding', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'onboarding'])->name('onboarding');
        Route::post('onboarding', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'completeOnboarding'])->name('onboarding.complete');
        Route::put('settings', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'updateSettings'])->name('settings.update');
        Route::get('settings/smtp', [\Modules\ERP\Http\Controllers\SmtpSettingController::class, 'edit'])->name('settings.smtp.edit');
        Route::put('settings/smtp', [\Modules\ERP\Http\Controllers\SmtpSettingController::class, 'update'])->name('settings.smtp.update');

        // ── Clients ──
        Route::get('clients/search', [\Modules\ERP\Http\Controllers\ClientController::class, 'search'])->name('clients.search');
        Route::get('clients/create', [\Modules\ERP\Http\Controllers\ClientController::class, 'create'])->name('clients.create');
        Route::get('clients/{client}/edit', [\Modules\ERP\Http\Controllers\ClientController::class, 'edit'])->name('clients.edit');
        Route::post('clients', [\Modules\ERP\Http\Controllers\ClientController::class, 'store'])->name('clients.store');
        Route::put('clients/{client}', [\Modules\ERP\Http\Controllers\ClientController::class, 'update'])->name('clients.update');
        Route::delete('clients/{client}', [\Modules\ERP\Http\Controllers\ClientController::class, 'destroy'])->name('clients.destroy');
        Route::get('clients/{client}', [\Modules\ERP\Http\Controllers\ClientController::class, 'show'])->name('clients.show');
        
        // ── Client Transactions ──
        Route::get('clients/{client}/wallet/adjust', [\Modules\ERP\Http\Controllers\WalletController::class, 'adjust'])->name('clients.wallet.adjust');
        Route::get('clients/{client}/wallet', [\Modules\ERP\Http\Controllers\WalletController::class, 'show'])->name('clients.wallet.index');
        Route::post('clients/{client}/wallet/receive', [\Modules\ERP\Http\Controllers\WalletController::class, 'receivePayment'])->name('clients.wallet.receive');
        Route::post('clients/{client}/wallet/send', [\Modules\ERP\Http\Controllers\WalletController::class, 'sendPayment'])->name('clients.wallet.send');
        Route::post('clients/{client}/wallet/refund', [\Modules\ERP\Http\Controllers\WalletController::class, 'refund'])->name('clients.wallet.refund');

        // ── Transactions ──
        Route::get('transactions/{transaction}', [\Modules\ERP\Http\Controllers\TransactionController::class, 'show'])->name('transactions.show');

        // ── Tasks & Todos ──
        Route::resource('tasks', \Modules\ERP\app\Features\Tasks\Controllers\TaskController::class);
        Route::post('tasks/{task}/items', [\Modules\ERP\app\Features\Tasks\Controllers\TaskController::class, 'storeItem'])->name('tasks.items.store');
        Route::put('tasks/{task}/items/{item}', [\Modules\ERP\app\Features\Tasks\Controllers\TaskController::class, 'updateItem'])->name('tasks.items.update');
        Route::post('tasks/{task}/items/{item}/complete', [\Modules\ERP\app\Features\Tasks\Controllers\TaskController::class, 'completeItem'])->name('tasks.items.complete');
        Route::post('tasks/{task}/items/sort', [\Modules\ERP\app\Features\Tasks\Controllers\TaskController::class, 'sortItems'])->name('tasks.items.sort');
        Route::post('tasks/{task}/items/{item}/pause', [\Modules\ERP\app\Features\Tasks\Controllers\TaskController::class, 'pauseItem'])->name('tasks.items.pause');
        Route::post('tasks/{task}/items/{item}/resume', [\Modules\ERP\app\Features\Tasks\Controllers\TaskController::class, 'resumeItem'])->name('tasks.items.resume');
        Route::delete('tasks/{task}/items/{item}', [\Modules\ERP\app\Features\Tasks\Controllers\TaskController::class, 'destroyItem'])->name('tasks.items.destroy');

        // ── Recurring Entries ──
        Route::resource('recurring', \Modules\ERP\Http\Controllers\RecurringController::class);

        // ── Withdrawals ──
        Route::resource('withdrawals', \Modules\ERP\Http\Controllers\WithdrawalController::class);

        // ── Payment Methods ──
        Route::resource('payment-methods', \Modules\ERP\Http\Controllers\PaymentMethodController::class);

        // ── Client & Tenant Notes ──
        Route::resource('client-notes', \Modules\ERP\Http\Controllers\ClientNoteController::class);
        Route::resource('tenant-notes', \Modules\ERP\Http\Controllers\TenantNoteController::class);

        // ── Invoices ──
        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.send-email');
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('invoices/{invoice}/pay-wallet', [InvoiceController::class, 'payWallet'])->name('invoices.pay-wallet');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

        // ── Projects ──
        Route::get('projects/create', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'create'])->name('projects.create');
        Route::get('projects/{project}/edit', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'edit'])->name('projects.edit');
        Route::get('projects/{project}', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'show'])->name('projects.show');
        Route::post('projects', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'store'])->name('projects.store');
        Route::put('projects/{project}', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'destroy'])->name('projects.destroy');

        // ── Calendar ──
        Route::get('calendar', [\Modules\ERP\app\Features\Calendar\Controllers\CalendarController::class, 'index'])->name('calendar.index');

        // ── Storage Providers ──
        Route::get('storage-providers/create', [\Modules\ERP\Http\Controllers\StorageProviderController::class, 'create'])->name('storage-providers.create');
        Route::post('storage-providers', [\Modules\ERP\Http\Controllers\StorageProviderController::class, 'store'])->name('storage-providers.store');
        
        // ── Files ──
        Route::get('files/create', [\Modules\ERP\Http\Controllers\FileController::class, 'create'])->name('files.create');
        Route::post('files', [\Modules\ERP\Http\Controllers\FileController::class, 'store'])->name('files.store');
        Route::get('files/{file}', [\Modules\ERP\Http\Controllers\FileController::class, 'show'])->name('files.show');
        Route::delete('files/{file}', [\Modules\ERP\Http\Controllers\FileController::class, 'destroy'])->name('files.destroy');
        // ── Backup ──
        Route::get('backup', [\Modules\ERP\Http\Controllers\BackupController::class, 'index'])->name('backup.index');
        Route::get('backup/download', [\Modules\ERP\Http\Controllers\BackupController::class, 'download'])->name('backup.download');
        Route::post('backup/restore', [\Modules\ERP\Http\Controllers\BackupController::class, 'restore'])->name('backup.restore');

        // ── Team Members ──
        Route::get('team-members', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'index'])->name('team-members.index');
        Route::post('team-members', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'store'])->name('team-members.store');
        Route::put('team-members/{id}', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'update'])->name('team-members.update');
        Route::delete('team-members/{id}', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'destroy'])->name('team-members.destroy');
        Route::get('tickets/create', [\Modules\ERP\Http\Controllers\TicketController::class, 'create'])->name('tickets.create');
        Route::post('tickets', [\Modules\ERP\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');
        Route::post('tickets/{ticket}/resolve', [\Modules\ERP\Http\Controllers\TicketController::class, 'resolve'])->name('tickets.resolve');
        Route::post('tickets/{ticket}/close', [\Modules\ERP\Http\Controllers\TicketController::class, 'close'])->name('tickets.close');
        Route::delete('tickets/{ticket}', [\Modules\ERP\Http\Controllers\TicketController::class, 'destroy'])->name('tickets.destroy');
        Route::post('notes/{note}/toggle-pin', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'togglePin'])->name('notes.togglePin');
        Route::resource('notes', \Modules\ERP\Http\Controllers\TenantNoteController::class)->except(['create', 'edit']);
        Route::resource('expenses', \Modules\ERP\Http\Controllers\ExpenseController::class)->except(['show']);

        Route::get('contracts/create', [\Modules\ERP\Http\Controllers\ContractController::class, 'create'])->name('contracts.create');
        Route::post('contracts', [\Modules\ERP\Http\Controllers\ContractController::class, 'store'])->name('contracts.store');

        // ── Referrals ──
        Route::get('referrals', [\Modules\ERP\Http\Controllers\ReferralController::class, 'index'])->name('referrals.index');
        Route::get('referrals/earnings', [\Modules\ERP\Http\Controllers\ReferralController::class, 'earnings'])->name('referrals.earnings');
        Route::get('referrals/tree/{client}', [\Modules\ERP\Http\Controllers\ReferralController::class, 'tree'])->name('referrals.tree');

        // ── Inventory ──
        Route::get('inventory', [\Modules\ERP\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
        Route::resource('inventory/products', \Modules\ERP\Http\Controllers\ProductController::class)->names('inventory.products');
        Route::get('inventory/products/{product}/adjust', [\Modules\ERP\Http\Controllers\ProductController::class, 'adjust'])->name('inventory.products.adjust');
        Route::post('inventory/products/{product}/adjust', [\Modules\ERP\Http\Controllers\ProductController::class, 'storeAdjustment'])->name('inventory.products.store_adjustment');

        // ── POS ──
        Route::get('pos', [\Modules\ERP\Http\Controllers\PosController::class, 'index'])->name('pos.index');
        
        // ── Branches ──
        Route::prefix('branches')->name('branches.')->group(function () {
            Route::post('switch', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchController::class, 'switchBranch'])->name('switch');
            Route::get('dashboard', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchController::class, 'dashboard'])->name('dashboard');
            Route::get('/', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchController::class, 'index'])->name('index');
            Route::post('/', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchController::class, 'store'])->name('store');
            Route::get('transfers', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchTransferController::class, 'index'])->name('transfers');
            Route::post('{branch}/transfers', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchTransferController::class, 'store'])->name('transfers.store');
        });
    });

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/erp')
    ->name('admin.erp.')
    ->group(function () {
        // Admin ERP management routes
    });
