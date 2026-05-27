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

        // ── Clients ──
        Route::post('clients', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'storeClient'])->name('clients.store');
        Route::put('clients/{client}', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'updateClient'])->name('clients.update');
        Route::delete('clients/{client}', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'destroyClient'])->name('clients.destroy');
        Route::get('clients/{client}', [\Modules\ERP\Http\Controllers\ClientController::class, 'show'])->name('clients.show');
        
        // ── Client Wallet ──
        Route::get('clients/{client}/wallet', [\Modules\ERP\Http\Controllers\WalletController::class, 'index'])->name('clients.wallet.index');
        Route::post('clients/{client}/wallet/credit', [\Modules\ERP\Http\Controllers\WalletController::class, 'credit'])->name('clients.wallet.credit');
        Route::post('clients/{client}/wallet/debit', [\Modules\ERP\Http\Controllers\WalletController::class, 'debit'])->name('clients.wallet.debit');
        Route::post('clients/{client}/wallet/lock', [\Modules\ERP\Http\Controllers\WalletController::class, 'lock'])->name('clients.wallet.lock');
        Route::post('clients/{client}/wallet/unlock', [\Modules\ERP\Http\Controllers\WalletController::class, 'unlock'])->name('clients.wallet.unlock');

        // ── Tasks & Todos ──
        Route::resource('tasks', \Modules\ERP\Http\Controllers\TaskController::class);
        Route::post('tasks/{task}/items', [\Modules\ERP\Http\Controllers\TaskController::class, 'storeItem'])->name('tasks.items.store');
        Route::put('tasks/{task}/items/{item}', [\Modules\ERP\Http\Controllers\TaskController::class, 'updateItem'])->name('tasks.items.update');
        Route::post('tasks/{task}/items/{item}/complete', [\Modules\ERP\Http\Controllers\TaskController::class, 'completeItem'])->name('tasks.items.complete');
        Route::post('tasks/{task}/items/sort', [\Modules\ERP\Http\Controllers\TaskController::class, 'sortItems'])->name('tasks.items.sort');
        Route::post('tasks/{task}/items/{item}/pause', [\Modules\ERP\Http\Controllers\TaskController::class, 'pauseItem'])->name('tasks.items.pause');
        Route::post('tasks/{task}/items/{item}/resume', [\Modules\ERP\Http\Controllers\TaskController::class, 'resumeItem'])->name('tasks.items.resume');
        Route::delete('tasks/{task}/items/{item}', [\Modules\ERP\Http\Controllers\TaskController::class, 'destroyItem'])->name('tasks.items.destroy');

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
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

        // ── Projects ──
        Route::post('projects', [\Modules\ERP\Http\Controllers\ProjectController::class, 'store'])->name('projects.store');
        Route::put('projects/{project}', [\Modules\ERP\Http\Controllers\ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [\Modules\ERP\Http\Controllers\ProjectController::class, 'destroy'])->name('projects.destroy');

        // ── Storage Providers ──
        Route::post('storage-providers', [\Modules\ERP\Http\Controllers\StorageProviderController::class, 'store'])->name('storage-providers.store');
        
        // ── Files ──
        Route::post('files', [\Modules\ERP\Http\Controllers\FileController::class, 'store'])->name('files.store');
        Route::get('files/{file}', [\Modules\ERP\Http\Controllers\FileController::class, 'show'])->name('files.show');
        Route::delete('files/{file}', [\Modules\ERP\Http\Controllers\FileController::class, 'destroy'])->name('files.destroy');

        // ── Missing Routes for Frontend ──
        Route::get('team-members', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('team-members.index');
        Route::post('tickets', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('tickets.store');
        Route::post('tickets/{ticket}/resolve', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('tickets.resolve');
        Route::post('tickets/{ticket}/close', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('tickets.close');
        Route::delete('tickets/{ticket}', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('tickets.destroy');
        Route::post('notes/{note}/toggle-pin', [\Modules\ERP\Http\Controllers\TenantNoteController::class, 'togglePin'])->name('notes.togglePin');
        Route::resource('notes', \Modules\ERP\Http\Controllers\TenantNoteController::class)->except(['create', 'edit']);
        Route::post('expenses', [\Modules\ERP\Http\Controllers\ERPDashboardController::class, 'index'])->name('expenses.store');
    });

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/erp')
    ->name('admin.erp.')
    ->group(function () {
        // Admin ERP management routes
    });
