<?php

use Illuminate\Support\Facades\Route;
use Modules\MusoftwarePayments\Http\Controllers\MusoftwarePaymentsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('musoftwarepayments', MusoftwarePaymentsController::class)->names('musoftwarepayments');
});
