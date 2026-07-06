<?php

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\SerialDeviceController;
use App\Http\Controllers\Api\SubscriptionSyncController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\WebhookController;
use App\Models\Currency;
use App\Models\GoldWorldPrice;
use App\Services\IpGeolocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
//
// Auth: shared-secret HMAC over the raw request body. The client must send
//       `X-Musoftwares-Signature: sha256=<hex>` and `services.serial_device.api_secret`
//       must be configured in the environment.
// Throttle: 60 req/min/IP (the middleware also records any 401 into the
//       blocked-IP pipeline via SecurityEnforcement).
// Returns: { "status": "active" } or { "status": "inactive" }

Route::post('serial/device',
    [SerialDeviceController::class, 'register']
)->middleware(['force.json', 'throttle:60,1']);

// ── Paid Commission Play ─────────────────────────────────────────────────────
// Read-only check used by external client software to determine whether the
// user has an active paid subscription for a given app id. Requires Sanctum
// auth; only reveals existence of a paid invoice item, never invoice/user
// details.
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('paid-commision-play',
        [\App\Http\Controllers\Api\CommissionController::class, 'checkStatus']
    );
});

// ── Runtime Version Manifest (public) ─────────────────────────────────────────
// Polled by local runtime agents to check for updates.
// Served from public/downloads/runtime/latest.json
Route::get('runtime/version', function () {
    $manifest = public_path('downloads/runtime/latest.json');
    if (! file_exists($manifest)) {
        return response()->json([
            'version' => '1.0.0',
            'minimum_supported' => '1.0.0',
            'channel' => 'stable',
            'downloads' => [],
            'changelog' => [],
        ]);
    }

    return response()->file($manifest, ['Content-Type' => 'application/json']);
})->name('api.runtime.version');

// ── Runtime Plugin Manifest (public) ─────────────────────────────────────────
// Lists all available plugins (no auth — only returns public metadata).
Route::get('runtime/plugins', function (Request $request) {
    $tools = collect(config('tools'))
        ->filter(fn ($t) => $t['is_active'] ?? false)
        ->map(fn ($t) => [
            'id' => $t['slug'],
            'name' => $t['title'],
            'slug' => $t['slug'],
            'runtime' => 'nodejs',
            'description' => $t['short_description'] ?? '',
            'version' => $t['version'] ?? '1.0.0',
        ])
        ->values();

    return response()->json(['plugins' => $tools]);
})->name('api.runtime.plugins');

// ── Email Tracker System ─────────────────────────────────────────────────────
// Handles 1x1 image tracking for Email Sender Pro local plugin
Route::get('t/open/{payload}.gif', [TrackerController::class, 'pixel'])
    ->name('api.tracker.pixel');

Route::get('t/click/{payload}', [TrackerController::class, 'click'])
    ->name('api.tracker.click');

Route::get('t/unsubscribe/{payload}', [TrackerController::class, 'unsubscribe'])
    ->name('api.tracker.unsubscribe');

Route::post('tracker/sync', [TrackerController::class, 'sync'])
    ->name('api.tracker.sync');

// ── Public: Bing daily images ────────────────────────────────────────────────
// Fetches daily images from Bing for backgrounds (e.g. desktop wallpapers)
Route::get('bing-daily-images', function () {
    $images = Cache::remember('auth_bing_images', 3600, function () {
        try {
            $response = Http::timeout(5)->get('https://www.bing.com/HPImageArchive.aspx', [
                'format' => 'js',
                'idx' => 0,
                'n' => 8,
                'mkt' => 'en-US',
            ]);
            if (! $response->successful()) {
                return [];
            }
            $data = $response->json();
            $list = $data['images'] ?? [];

            return array_values(array_filter(array_map(function ($img) {
                $url = $img['url'] ?? '';

                return $url ? 'https://www.bing.com'.$url : null;
            }, $list)));
        } catch (Throwable $e) {
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
    Route::post('send-otp', [MobileAuthController::class, 'sendOtp']);
    Route::post('verify-otp', [MobileAuthController::class, 'verifyOtp']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [MobileAuthController::class, 'me']);
    Route::put('user/profile', [MobileAuthController::class, 'updateProfile']);
});

// ── Mobile Freelance API ────────────────────────────────────────────────────
// Handled by the Freelance module's MobileApiController (already exists).

if (file_exists(base_path('Modules/Freelance/routes/api.php'))) {
    require base_path('Modules/Freelance/routes/api.php');
}

// ── Incoming Webhooks ────────────────────────────────────────────────────────
// Handles all incoming webhooks from external providers
Route::post('webhooks/incoming/{source}', [WebhookController::class, 'handle'])
    ->name('api.webhooks.incoming');

// ── Geolocation ──────────────────────────────────────────────────────────────
Route::get('ip-country', function (Request $request) {
    $ip = $request->ip();
    $service = new IpGeolocationService;
    $country = $service->getCountryFromIp($ip);
    $currency = $service->getCurrencyCodeForCountry($country);

    return response()->json([
        'ip' => $ip,
        'country' => $country,
        'currency' => $currency,
    ]);
})->name('api.ip-country');

// ── Currencies ───────────────────────────────────────────────────────────────
// Provides all currencies, their USD exchange rates, and global gold prices. Used by ERP and modules.
Route::get('currencies', function () {
    $currencies = Currency::all();
    $rates = CurrencyHelper::prepare(date('Y-m-d'));

    $usdRates = [];
    foreach ($currencies as $currency) {
        $code = strtoupper($currency->currency);
        if (isset($rates[$code])) {
            $usdRates[$currency->id] = $rates[$code];
        }
    }

    $latestGoldWorldPrice = GoldWorldPrice::orderBy('price_date', 'desc')->first();

    return response()->json([
        'currencies' => $currencies,
        'usd_rates' => $usdRates,
        'gold_world_price' => $latestGoldWorldPrice,
    ]);
})->name('api.currencies');

Route::post('/sso/verify', [SsoController::class, 'verify'])->name('sso.verify');
Route::post('/sso/subscriptions/sync', [SubscriptionSyncController::class, 'sync'])->name('sso.subscriptions.sync');
