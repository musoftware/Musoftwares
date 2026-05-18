<?php

use Illuminate\Support\Facades\Route;
use Modules\Intelligence\Http\Controllers\IntelligenceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('intelligences', IntelligenceController::class)->names('intelligence');
});
