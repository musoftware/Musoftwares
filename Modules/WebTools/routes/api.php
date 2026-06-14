<?php

use Illuminate\Support\Facades\Route;
use Modules\WebTools\Http\Controllers\WebToolsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('webtools', WebToolsController::class)->names('webtools');
});
