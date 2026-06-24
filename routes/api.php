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

Route::post('serial/device',
    [\App\Http\Controllers\Api\SerialDeviceController::class, 'register']
)->middleware('force.json');

// ── Runtime Version Manifest (public) ─────────────────────────────────────────
// Polled by local runtime agents to check for updates.
// Served from public/downloads/runtime/latest.json
Route::get('runtime/version', function () {
    $manifest = public_path('downloads/runtime/latest.json');
    if (!file_exists($manifest)) {
        return response()->json([
            'version'           => '1.0.0',
            'minimum_supported' => '1.0.0',
            'channel'           => 'stable',
            'downloads'         => [],
            'changelog'         => [],
        ]);
    }
    return response()->file($manifest, ['Content-Type' => 'application/json']);
})->name('api.runtime.version');

// ── Runtime Plugin Manifest (public) ─────────────────────────────────────────
// Lists all available plugins (no auth — only returns public metadata).
Route::get('runtime/plugins', function (\Illuminate\Http\Request $request) {
    $tools = collect(config('tools'))
        ->filter(fn($t) => $t['is_active'] ?? false)
        ->map(fn($t) => [
            'id'          => $t['slug'],
            'name'        => $t['title'],
            'slug'        => $t['slug'],
            'runtime'     => 'nodejs',
            'description' => $t['short_description'] ?? '',
            'version'     => $t['version'] ?? '1.0.0',
        ])
        ->values();

    return response()->json(['plugins' => $tools]);
})->name('api.runtime.plugins');

// ── Email Tracker System ─────────────────────────────────────────────────────
// Handles 1x1 image tracking for Email Sender Pro local plugin
Route::get('t/open/{payload}.gif', [\App\Http\Controllers\TrackerController::class, 'pixel'])
    ->name('api.tracker.pixel');

Route::get('t/click/{payload}', [\App\Http\Controllers\TrackerController::class, 'click'])
    ->name('api.tracker.click');

Route::get('t/unsubscribe/{payload}', [\App\Http\Controllers\TrackerController::class, 'unsubscribe'])
    ->name('api.tracker.unsubscribe');

Route::post('tracker/sync', [\App\Http\Controllers\TrackerController::class, 'sync'])
    ->name('api.tracker.sync');

// ── Public: Bing daily images ────────────────────────────────────────────────
// Fetches daily images from Bing for backgrounds (e.g. desktop wallpapers)
Route::get('bing-daily-images', function () {
    $images = \Illuminate\Support\Facades\Cache::remember('auth_bing_images', 3600, function () {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get('https://www.bing.com/HPImageArchive.aspx', [
                'format' => 'js',
                'idx' => 0,
                'n' => 8,
                'mkt' => 'en-US',
            ]);
            if (!$response->successful()) {
                return [];
            }
            $data = $response->json();
            $list = $data['images'] ?? [];
            return array_values(array_filter(array_map(function ($img) {
                $url = $img['url'] ?? '';
                return $url ? 'https://www.bing.com' . $url : null;
            }, $list)));
        } catch (\Throwable $e) {
            return [];
        }
    });
    return response()->json($images ?: []);
})->name('api.bing-daily-images');

if (file_exists(base_path('Modules/CRM/routes/api.php'))) {
    require base_path('Modules/CRM/routes/api.php');
}

// ── Mobile App Auth (OTP) ──────────────────────────────────────────────────
// Phone number + OTP flow for the React Native Freelancer mobile app.

Route::prefix('auth')->group(function () {
    Route::post('send-otp',    [\App\Http\Controllers\Api\MobileAuthController::class, 'sendOtp']);
    Route::post('verify-otp',  [\App\Http\Controllers\Api\MobileAuthController::class, 'verifyOtp']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user',          [\App\Http\Controllers\Api\MobileAuthController::class, 'me']);
    Route::put('user/profile',  [\App\Http\Controllers\Api\MobileAuthController::class, 'updateProfile']);
});

// ── Mobile Freelance API ────────────────────────────────────────────────────
// Handled by the Freelance module's MobileApiController (already exists).

if (file_exists(base_path('Modules/Freelance/routes/api.php'))) {
    require base_path('Modules/Freelance/routes/api.php');
}

// â”€â”€ Incoming Webhooks â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Handles all incoming webhooks from external providers
Route::post('webhooks/incoming/{source}', [\App\Http\Controllers\WebhookController::class, 'handle'])
    ->name('api.webhooks.incoming');



Route::post('/sso/verify', [\App\Http\Controllers\SsoController::class, 'verify'])->name('sso.verify');
Route::post('/sso/subscriptions/sync', [\App\Http\Controllers\Api\SubscriptionSyncController::class, 'sync'])->name('sso.subscriptions.sync');
