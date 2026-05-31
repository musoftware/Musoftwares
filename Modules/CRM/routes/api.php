<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\Api\WebhookReceiveController;

Route::prefix('crm')->name('api.crm.')->group(function () {
    // Open webhook for Zapier/Make
    Route::post('/webhook', [WebhookReceiveController::class, 'handle'])->name('webhook.receive');
});
