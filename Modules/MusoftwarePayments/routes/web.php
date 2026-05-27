<?php

use Illuminate\Support\Facades\Route;
use Modules\MusoftwarePayments\Http\Controllers\MusoftwarePaymentsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('musoftwarepayments', MusoftwarePaymentsController::class)->names('musoftwarepayments');
});
