<?php

use Illuminate\Support\Facades\Route;
use Modules\ERP\Http\Controllers\InvoiceController;

Route::middleware(['web', 'auth', 'tenant.active'])
    ->prefix('erp')
    ->name('erp.')
    ->group(function () {
        // ERP routes for tenants
        Route::resource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.download');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    });

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/erp')
    ->name('admin.erp.')
    ->group(function () {
        // Admin ERP management routes
    });
