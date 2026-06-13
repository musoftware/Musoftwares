<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Freelance\Http\Controllers\ShortcutNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/freelance/shortcut/notifications', [ShortcutNotificationController::class, 'fetch']);

    // Mobile API Routes
    Route::prefix('freelance/mobile')->group(function () {
        Route::get('/jobs',                  [\Modules\Freelance\Http\Controllers\Api\MobileApiController::class, 'getJobs']);
        Route::post('/jobs',                 [\Modules\Freelance\Http\Controllers\Api\MobileApiController::class, 'storeJob']);
        Route::get('/jobs/my',               [\Modules\Freelance\Http\Controllers\Api\MobileApiController::class, 'getMyJobs']);
        Route::get('/jobs/{id}',             [\Modules\Freelance\Http\Controllers\Api\MobileApiController::class, 'getJobDetail']);
        Route::get('/negotiations',          [\Modules\Freelance\Http\Controllers\Api\MobileApiController::class, 'getNegotiations']);
        Route::post('/proposals/{id}/negotiate', [\Modules\Freelance\Http\Controllers\Api\MobileApiController::class, 'negotiateProposal']);
        Route::post('/proposals/{id}/accept',    [\Modules\Freelance\Http\Controllers\Api\MobileApiController::class, 'acceptProposal']);
        Route::post('/proposals/{id}/reject',    [\Modules\Freelance\Http\Controllers\Api\MobileApiController::class, 'rejectProposal']);
    });
});
