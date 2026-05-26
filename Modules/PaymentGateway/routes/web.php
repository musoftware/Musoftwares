<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentGateway\Http\Controllers\Admin\AdminGatewayClientController;

/*
|--------------------------------------------------------------------------
| Admin Web Routes — Payment Gateway
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are covered
| by the admin middleware group defined in web.php.
|
*/

Route::middleware(['web', 'auth', 'verified', 'onboarding', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ── Gateway Clients ──────────────────────────────────────────────
        Route::prefix('musoftware-clients')->name('musoftware-clients.')->group(function () {
            Route::get('/',             [AdminGatewayClientController::class, 'index'])   ->name('index');
            Route::post('/',            [AdminGatewayClientController::class, 'store'])   ->name('store');
            Route::get('/{gatewayClient}',      [AdminGatewayClientController::class, 'show'])    ->name('show');
            Route::put('/{gatewayClient}',      [AdminGatewayClientController::class, 'update'])  ->name('update');
            Route::delete('/{gatewayClient}',   [AdminGatewayClientController::class, 'destroy']) ->name('destroy');
            Route::post('/{gatewayClient}/regenerate-secret',
                [AdminGatewayClientController::class, 'regenerateSecret'])
                ->name('regenerate-secret');
        });
    });
