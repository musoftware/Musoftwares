<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are stateless and serve external clients (software, mobile apps).
| Rate limiting is applied via config/app.php throttle settings.
|
*/

// ── Serial License Check-In ──────────────────────────────────────────────────
// Called by client software on startup to verify license status.
// No authentication — device_id is the identity.
// Returns: { "status": "active" } or { "status": "inactive" }
Route::post('serial/device', [\App\Http\Controllers\Api\SerialDeviceController::class, 'register'])
    ->middleware('throttle:60,1')
    ->name('api.serial.register');
