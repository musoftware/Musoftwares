<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'tenant.active'])
    ->prefix('erp')
    ->name('erp.')
    ->group(function () {
        // ERP routes for tenants
    });

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin/erp')
    ->name('admin.erp.')
    ->group(function () {
        // Admin ERP management routes
    });