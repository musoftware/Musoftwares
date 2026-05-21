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

