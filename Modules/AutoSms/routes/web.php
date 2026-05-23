<?php

use Illuminate\Support\Facades\Route;
use Modules\AutoSms\Http\Controllers\AutoSmsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['web', 'auth', 'verified', 'onboarding', 'subscription:intelligence'])->prefix('intelligence/isaas/auto-sms')->name('intelligence.isaas.autosms.')->group(function () {
    Route::get('/', [AutoSmsController::class, 'index'])->name('index');
});
