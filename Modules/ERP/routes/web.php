<?php

use Illuminate\Support\Facades\Route;
use Modules\ERP\Http\Controllers\InvoiceController;

// ── Dashboard & Onboarding for Primary User ──
Route::middleware(['web', 'auth'])
    ->prefix('erp')
    ->name('erp.')
    ->group(function () {
        Route::get('onboarding', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'onboarding'])->name('onboarding');
        Route::post('onboarding', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'completeOnboarding'])->name('onboarding.complete');
        
        Route::get('bridge', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'bridge'])->name('bridge');
    });

// ── Authentication for ERP Team Members ──
Route::middleware(['web'])
    ->prefix('erp')
    ->name('erp.')
    ->group(function () {
        Route::get('login', [\Modules\ERP\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [\Modules\ERP\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.submit');
        Route::post('logout', [\Modules\ERP\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout')->middleware('auth:erp_team');
        
        Route::get('invite/{id}/accept', [\Modules\ERP\Http\Controllers\Auth\InviteController::class, 'showAcceptForm'])->name('invite.accept');
        Route::post('invite/{id}/accept', [\Modules\ERP\Http\Controllers\Auth\InviteController::class, 'accept'])->name('invite.submit');
    });

// ── ERP Module Routes for Team Members ──
Route::middleware(['web', 'auth:erp_team', 'tenant.active', 'erp.team.permissions'])
    ->prefix('erp')
    ->name('erp.')
    ->group(function () {
        Route::get('dashboard', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('dashboard');

        // ── Settings ──
        // Route::get('settings/profile', [\Modules\ERP\Http\Controllers\Admin\TenantSettingsController::class, 'editProfile'])->name('settings.profile.edit');
        Route::put('settings', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'updateSettings'])->name('settings.update');

        // ── Accounting ──
        Route::prefix('accounting')->name('accounting.')->group(function () {
            Route::resource('chart-of-accounts', \Modules\ERP\Http\Controllers\Accounting\ChartOfAccountController::class);
            Route::resource('journal-entries', \Modules\ERP\Http\Controllers\Accounting\JournalEntryController::class);
            Route::post('journal-entries/{journal_entry}/post', [\Modules\ERP\Http\Controllers\Accounting\JournalEntryController::class, 'post'])->name('journal-entries.post');
            Route::resource('rules', \Modules\ERP\Http\Controllers\Accounting\AccountingRuleController::class);
        });

        // ── Clients ──
        Route::get('clients', [\Modules\ERP\Http\Controllers\ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/search', [\Modules\ERP\Http\Controllers\ClientController::class, 'search'])->name('clients.search');
        Route::get('clients/create', [\Modules\ERP\Http\Controllers\ClientController::class, 'create'])->name('clients.create');
        Route::get('clients/{client}/edit', [\Modules\ERP\Http\Controllers\ClientController::class, 'edit'])->name('clients.edit');
        Route::post('clients', [\Modules\ERP\Http\Controllers\ClientController::class, 'store'])->name('clients.store');
        Route::put('clients/{client}', [\Modules\ERP\Http\Controllers\ClientController::class, 'update'])->name('clients.update');
        Route::put('clients/{client}/status', [\Modules\ERP\Http\Controllers\ClientController::class, 'updateStatus'])->name('clients.updateStatus');
        Route::delete('clients/{client}', [\Modules\ERP\Http\Controllers\ClientController::class, 'destroy'])->name('clients.destroy');
        Route::get('clients/{client}', [\Modules\ERP\Http\Controllers\ClientController::class, 'show'])->name('clients.show');
        Route::get('clients/{client}/transactions', [\Modules\ERP\Http\Controllers\ClientController::class, 'transactions'])->name('clients.transactions');
        Route::get('clients/{client}/files', [\Modules\ERP\Http\Controllers\ClientController::class, 'files'])->name('clients.files');
        Route::get('clients/{client}/notes', [\Modules\ERP\Http\Controllers\ClientController::class, 'notes'])->name('clients.notes');
        
        // ── Client Transactions ──
        Route::get('clients/{client}/wallet/adjust', [\Modules\ERP\Http\Controllers\WalletController::class, 'adjust'])->name('clients.wallet.adjust');
        Route::get('clients/{client}/wallet', [\Modules\ERP\Http\Controllers\WalletController::class, 'show'])->name('clients.wallet.index');
        Route::post('clients/{client}/wallet/receive', [\Modules\ERP\Http\Controllers\WalletController::class, 'receivePayment'])->name('clients.wallet.receive');
        Route::post('clients/{client}/wallet/send', [\Modules\ERP\Http\Controllers\WalletController::class, 'sendPayment'])->name('clients.wallet.send');
        Route::post('clients/{client}/wallet/refund', [\Modules\ERP\Http\Controllers\WalletController::class, 'refund'])->name('clients.wallet.refund');
        Route::post('clients/{client}/wallet/bonus', [\Modules\ERP\Http\Controllers\WalletController::class, 'addBonus'])->name('clients.wallet.bonus');

        // ── Transactions ──
        Route::get('transactions/{transaction}', [\Modules\ERP\Http\Controllers\TransactionController::class, 'show'])->name('transactions.show');

        // ── Tasks & Todos ──
        Route::resource('tasks', \Modules\ERP\Http\Controllers\TaskController::class);
        Route::post('tasks/{task}/items', [\Modules\ERP\Http\Controllers\TaskController::class, 'storeItem'])->name('tasks.items.store');
        Route::put('tasks/{task}/items/{item}', [\Modules\ERP\Http\Controllers\TaskController::class, 'updateItem'])->name('tasks.items.update');
        Route::post('tasks/{task}/items/{item}/complete', [\Modules\ERP\Http\Controllers\TaskController::class, 'completeItem'])->name('tasks.items.complete');
        Route::post('tasks/{task}/items/sort', [\Modules\ERP\Http\Controllers\TaskController::class, 'sortItems'])->name('tasks.items.sort');
        Route::post('tasks/{task}/items/{item}/pause', [\Modules\ERP\Http\Controllers\TaskController::class, 'pauseItem'])->name('tasks.items.pause');
        Route::post('tasks/{task}/items/{item}/resume', [\Modules\ERP\Http\Controllers\TaskController::class, 'resumeItem'])->name('tasks.items.resume');
        Route::delete('tasks/{task}/items/{item}', [\Modules\ERP\Http\Controllers\TaskController::class, 'destroyItem'])->name('tasks.items.destroy');
        Route::post('tasks/{task}/comments', [\Modules\ERP\Http\Controllers\TaskController::class, 'storeComment'])->name('tasks.comments.store');
        Route::delete('tasks/{task}/comments/{comment}', [\Modules\ERP\Http\Controllers\TaskController::class, 'destroyComment'])->name('tasks.comments.destroy');

        // ── Recurring Entries ──
        Route::resource('recurring', \Modules\ERP\Http\Controllers\RecurringController::class);

        // ── Withdrawals ──
        Route::post('withdrawals/{withdrawal}/approve', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'approve'])->name('withdrawals.approve');
        Route::post('withdrawals/{withdrawal}/mark-paid', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'markPaid'])->name('withdrawals.mark-paid');
        Route::post('withdrawals/{withdrawal}/reject', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'reject'])->name('withdrawals.reject');
        Route::post('withdrawals/{withdrawal}/cancel', [\Modules\ERP\Http\Controllers\WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');
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
        Route::post('invoices/{invoice}/costs/{cost}/mark-paid', [InvoiceController::class, 'markCostPaid'])->name('invoices.costs.mark-paid');
        Route::post('invoices/{invoice}/pay-wallet', [InvoiceController::class, 'payWallet'])->name('invoices.pay-wallet');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

        // ── Projects ──
        Route::get('projects/search', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'search'])->name('projects.search');
        Route::get('projects/create', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'create'])->name('projects.create');
        Route::get('projects/{project}/edit', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'edit'])->name('projects.edit');
        Route::get('projects/{project}', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'show'])->name('projects.show');
        Route::post('projects', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'store'])->name('projects.store');
        Route::put('projects/{project}', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [\Modules\ERP\app\Features\Projects\Controllers\ProjectController::class, 'destroy'])->name('projects.destroy');

        // ── Calendar ──
        Route::get('calendar', [\Modules\ERP\app\Features\Calendar\Controllers\CalendarController::class, 'index'])->name('calendar.index');
        
        // ── Files ──
        Route::get('files', [\Modules\ERP\Http\Controllers\FileController::class, 'index'])->name('files.index');
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
        Route::post('team-members/{id}/resend-invite', [\Modules\ERP\Http\Controllers\Team\TeamMemberController::class, 'resendInvite'])->name('team-members.resend-invite');
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
        Route::get('inventory/products/search', [\Modules\ERP\Http\Controllers\ProductController::class, 'search'])->name('inventory.products.search');
        Route::resource('inventory/products', \Modules\ERP\Http\Controllers\ProductController::class)->names('inventory.products');
        Route::get('inventory/products/{product}/adjust', [\Modules\ERP\Http\Controllers\ProductController::class, 'adjust'])->name('inventory.products.adjust');
        Route::post('inventory/products/{product}/adjust', [\Modules\ERP\Http\Controllers\ProductController::class, 'storeAdjustment'])->name('inventory.products.store_adjustment');
        
        // Product Categories
        Route::get('inventory/categories', [\Modules\ERP\Http\Controllers\ProductCategoryController::class, 'index'])->name('inventory.categories.index');
        Route::post('inventory/categories', [\Modules\ERP\Http\Controllers\ProductCategoryController::class, 'store'])->name('inventory.categories.store');
        Route::delete('inventory/categories/{category}', [\Modules\ERP\Http\Controllers\ProductCategoryController::class, 'destroy'])->name('inventory.categories.destroy');

        // ── Warehouse ──
        Route::prefix('warehouse')->name('warehouse.')->group(function () {
            Route::resource('warehouses', \Modules\ERP\Http\Controllers\Warehouse\WarehouseController::class);
            Route::resource('transfers', \Modules\ERP\Http\Controllers\Warehouse\StockTransferController::class)->only(['index', 'store']);
        });

        // ── Procurement ──
        Route::prefix('procurement')->name('procurement.')->group(function () {
            Route::resource('suppliers', \Modules\ERP\Http\Controllers\Procurement\SupplierController::class);
            Route::resource('purchase-orders', \Modules\ERP\Http\Controllers\Procurement\PurchaseOrderController::class);
            Route::post('purchase-orders/{purchase_order}/approve', [\Modules\ERP\Http\Controllers\Procurement\PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
            Route::post('purchase-orders/{purchase_order}/receive', [\Modules\ERP\Http\Controllers\Procurement\PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        });

        // ── Tax Engine ──
        Route::prefix('tax')->name('tax.')->group(function () {
            Route::resource('rates', \Modules\ERP\Http\Controllers\Tax\TaxRateController::class);
        });

        // ── Asset Management ──
        Route::prefix('assets')->name('assets.')->group(function () {
            Route::resource('fixed-assets', \Modules\ERP\Http\Controllers\Asset\FixedAssetController::class);
            Route::resource('categories', \Modules\ERP\Http\Controllers\Asset\AssetCategoryController::class)->only(['store', 'destroy']);
        });

        // ── Approval Engine ──
        Route::prefix('approvals')->name('approvals.')->group(function () {
            Route::resource('workflows', \Modules\ERP\Http\Controllers\Approval\WorkflowController::class);
            Route::resource('requests', \Modules\ERP\Http\Controllers\Approval\ApprovalRequestController::class)->only(['index', 'show']);
        });

        // ── Calendar ──
        Route::resource('calendar', \Modules\ERP\Http\Controllers\Calendar\CalendarEventController::class);

        // ── POS ──
        Route::get('pos', [\Modules\ERP\Http\Controllers\PosController::class, 'index'])->name('pos.index');
        Route::post('pos/checkout', [\Modules\ERP\Http\Controllers\PosController::class, 'checkout'])->name('pos.checkout');
        
        // ── Debts / Quick Ledger ──
        Route::get('debts', [\Modules\ERP\Http\Controllers\DebtController::class, 'index'])->name('debts.index');
        Route::get('debts/transactions/create', [\Modules\ERP\Http\Controllers\DebtTransactionController::class, 'create'])->name('debts.transactions.create');
        Route::post('debts/transactions', [\Modules\ERP\Http\Controllers\DebtTransactionController::class, 'store'])->name('debts.transactions.store');
        Route::delete('debts/transactions/{transaction}', [\Modules\ERP\Http\Controllers\DebtTransactionController::class, 'destroy'])->name('debts.transactions.destroy');
        Route::get('debts/client/{client}', [\Modules\ERP\Http\Controllers\DebtController::class, 'show'])->name('debts.show');
        
        // ── Branches ──
        Route::prefix('branches')->name('branches.')->group(function () {
            Route::post('switch', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchController::class, 'switchBranch'])->name('switch');
            Route::get('dashboard', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchController::class, 'dashboard'])->name('dashboard');
            Route::get('/', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchController::class, 'index'])->name('index');
            Route::post('/', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchController::class, 'store'])->name('store');
            Route::get('transfers', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchTransferController::class, 'index'])->name('transfers');
            Route::post('{branch}/transfers', [\Modules\ERP\app\Features\MultiBranch\Controllers\BranchTransferController::class, 'store'])->name('transfers.store');
        });

        // ── Payroll ──
        Route::prefix('payroll')->name('payroll.')->group(function () {
            Route::get('/', [\Modules\ERP\Http\Controllers\PayrollController::class, 'index'])->name('index');
            Route::post('contract', [\Modules\ERP\Http\Controllers\PayrollController::class, 'updateContract'])->name('contract.update');
            Route::post('generate', [\Modules\ERP\Http\Controllers\PayrollController::class, 'generate'])->name('generate');
            Route::post('payslips/{id}/items', [\Modules\ERP\Http\Controllers\PayrollController::class, 'updatePayslipItems'])->name('payslips.items.update');
            Route::post('payslips/{id}/mark-paid', [\Modules\ERP\Http\Controllers\PayrollController::class, 'markAsPaid'])->name('payslips.mark_paid');
        });

        // ── Team Member Portal ──
        Route::prefix('team/portal')->name('team.portal.')->group(function () {
            Route::get('/', [\Modules\ERP\Http\Controllers\Team\TeamPortalController::class, 'index'])->name('index');
            Route::post('clock-in', [\Modules\ERP\Http\Controllers\Team\TeamPortalController::class, 'clockIn'])->name('clock-in');
            Route::post('clock-out', [\Modules\ERP\Http\Controllers\Team\TeamPortalController::class, 'clockOut'])->name('clock-out');
            Route::post('leave-request', [\Modules\ERP\Http\Controllers\Team\TeamPortalController::class, 'requestLeave'])->name('leave-request');
        });

        // ── Manager Portal ──
        Route::prefix('manager')->name('manager.')->group(function () {
            // Approvals
            Route::get('approvals', [\Modules\ERP\Http\Controllers\Manager\ApprovalController::class, 'index'])->name('approvals.index');
            Route::get('approvals/leave/{leaveRequest}', [\Modules\ERP\Http\Controllers\Manager\ApprovalController::class, 'showLeave'])->name('approvals.leave.show');
            Route::post('approvals/leave/{leaveRequest}/approve', [\Modules\ERP\Http\Controllers\Manager\ApprovalController::class, 'approveLeave'])->name('approvals.leave.approve');
            Route::post('approvals/leave/{leaveRequest}/reject', [\Modules\ERP\Http\Controllers\Manager\ApprovalController::class, 'rejectLeave'])->name('approvals.leave.reject');
            
            // Reports & Exports
            Route::get('reports/export', [\Modules\ERP\Http\Controllers\Manager\ReportController::class, 'export'])->name('reports.export');
        });
    });

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/erp')
    ->name('admin.erp.')
    ->group(function () {
        // Admin ERP management routes
    });
