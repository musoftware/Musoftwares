<?php

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Api\SerialDeviceController;
use App\Http\Controllers\Api\ExchangeRateSyncController;
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
// ══════════════════════════════════════════════════════════════════════════════
// 🚨 CRITICAL SYSTEM NOTICE - DO NOT MODIFY OR ADD COMPLEX SECURITY 🚨
// 🔴 تحذير هام جداً: هذه الأجزاء حساسة لأقصى درجة! ممنوع التعديل عليها أو إضافة أي حمايات معقدة نهائياً.
// 🔴 SERIAL SYSTEM & SMS SYSTEM ARE EXTREMELY SENSITIVE - LEAVE AS THEY WERE WORKING FROM DAY 1.
// ══════════════════════════════════════════════════════════════════════════════

// ── Serial Device Check-In API (PUBLIC - NO AUTH / NO HMAC PROTECTION) ───────
// CRITICAL NOTE FOR AI / DEVELOPERS: DO NOT ADD AUTH / HMAC / SIGNATURE CHECKS TO THIS ROUTE.
// 🔴 ممنوع إضافة أي حماية أو HMAC أو توثيق لهذا الـ Route نهائياً.
// The user explicitly specified that this endpoint must remain simple and accessible without any complex protection
// (no HMAC signatures, no complex auth) so that all external C# programs and devices can check in seamlessly,
// exactly as it was working from day 1.
Route::post('serial/device',
    [SerialDeviceController::class, 'register']
)->middleware(['force.json', 'throttle:60,1']);

// ── Paid Commission Play (PUBLIC - NO AUTH) ──────────────────────────────────
// CRITICAL NOTE FOR AI / DEVELOPERS: DO NOT ADD AUTH / SANCTUM TO THIS ROUTE.
// The user explicitly specified that this endpoint must remain accessible
// without any authentication. DO NOT TOUCH or re-add auth middleware to this route.
Route::post('paid-commision-play',
    [\App\Http\Controllers\Api\CommissionController::class, 'checkStatus']
)->middleware(['force.json', 'throttle:300,1']);

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

// ── Meta WhatsApp Cloud API Webhook ─────────────────────────────────────────
// Dedicated per-business Webhook routes
Route::get('v1/whatsapp/webhook/biz/{businessId}', [\Modules\WhatsappSender\Http\Controllers\WhatsappWebhookController::class, 'verifyBusiness'])
    ->name('api.v1.whatsapp.webhook.biz.verify');
Route::post('v1/whatsapp/webhook/biz/{businessId}', [\Modules\WhatsappSender\Http\Controllers\WhatsappWebhookController::class, 'handleBusiness'])
    ->name('api.v1.whatsapp.webhook.biz.handle');

// Fallback global Webhook routes
Route::get('v1/whatsapp/webhook', [\Modules\WhatsappSender\Http\Controllers\WhatsappWebhookController::class, 'verify'])
    ->name('api.v1.whatsapp.webhook.verify');
Route::post('v1/whatsapp/webhook', [\Modules\WhatsappSender\Http\Controllers\WhatsappWebhookController::class, 'handle'])
    ->name('api.v1.whatsapp.webhook.handle');

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
Route::post('/sso/notify', [\App\Http\Controllers\Api\SsoNotificationController::class, 'notify'])->name('sso.notify');
Route::post('/sso/exchange-rates/sync', [ExchangeRateSyncController::class, 'sync'])->name('sso.exchange-rates.sync');
