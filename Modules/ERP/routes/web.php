<?php

use Illuminate\Support\Facades\Route;

use Modules\ERP\Http\Controllers\InvoiceController;

Route::middleware(['web', 'auth', 'tenant.active'])
    ->prefix('erp')
    ->name('erp.')
    ->group(function () {
        // ERP routes for tenants
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    });

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/erp')
    ->name('admin.erp.')
    ->group(function () {
        // Admin ERP management routes
    });