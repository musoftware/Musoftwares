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

        // Project routes
        Route::post('projects', [\Modules\ERP\Http\Controllers\ProjectController::class, 'store'])->name('projects.store');
        Route::put('projects/{project}', [\Modules\ERP\Http\Controllers\ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [\Modules\ERP\Http\Controllers\ProjectController::class, 'destroy'])->name('projects.destroy');

        // Storage routes
        Route::post('storage-providers', [\Modules\ERP\Http\Controllers\StorageProviderController::class, 'store'])->name('storage-providers.store');
        
        // File routes
        Route::post('files', [\Modules\ERP\Http\Controllers\FileController::class, 'store'])->name('files.store');
        Route::get('files/{file}', [\Modules\ERP\Http\Controllers\FileController::class, 'show'])->name('files.show');
        Route::delete('files/{file}', [\Modules\ERP\Http\Controllers\FileController::class, 'destroy'])->name('files.destroy');
    });

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/erp')
    ->name('admin.erp.')
    ->group(function () {
        // Admin ERP management routes
    });
