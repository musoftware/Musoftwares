<?php

use Illuminate\Support\Facades\Route;
use Modules\Tools\Http\Controllers\Api\AuthController;
use Modules\Tools\Http\Controllers\Api\LicenseController;
use Modules\Tools\Http\Controllers\Api\UpdateController;

/*
|--------------------------------------------------------------------------
| Tools Marketplace API Routes
|--------------------------------------------------------------------------
| These routes are called by Python desktop applications.
| All routes except /login return JSON.
|--------------------------------------------------------------------------
*/

Route::prefix('tools')->name('api.tools.')->group(function () {

    // ─── Desktop Authentication ─────────────────────────────────────────────
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    // ─── Sanctum-Protected Routes ───────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        // License Management
        Route::post('/license/activate', [LicenseController::class, 'activate'])->name('license.activate');
        Route::post('/license/check', [LicenseController::class, 'check'])->name('license.check');
        Route::post('/license/heartbeat', [LicenseController::class, 'heartbeat'])->name('license.heartbeat');

        // Update System
        Route::get('/{slug}/update-check', [UpdateController::class, 'check'])->name('update.check');
        Route::get('/{slug}/releases', [UpdateController::class, 'releases'])->name('releases');

        // ─── Agent Plugin Sync ──────────────────────────────────────────────
        // Polled by local agents to get list of subscribed plugins to auto-download.
        // Free tools (is_free=true) are automatically included for every authenticated user.
        Route::get('/agent/plugins', function (\Illuminate\Http\Request $request) {
            $agentType = $request->query('agent', 'nodejs'); // 'nodejs' or 'python'

            // Helper to check if a physical plugin file exists on the server
            $pluginFileExists = function (string $slug, $latestVersion) {
                if (file_exists(public_path("plugins/{$slug}.msp"))) {
                    return true;
                }
                if (file_exists(public_path("plugins/{$slug}.zip"))) {
                    return true;
                }
                if ($latestVersion && $latestVersion->file_path && \Illuminate\Support\Facades\Storage::exists($latestVersion->file_path)) {
                    return true;
                }
                return false;
            };

            // ── 1. Subscription-based plugins ──────────────────────────────
            $subscriptions = \Modules\Tools\Models\ToolSubscription::where('user_id', auth()->id())
                ->where('status', 'active')
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->get();

            $paidPlugins = $subscriptions
                ->filter(fn($s) => $s->tool)
                ->filter(function ($s) use ($agentType, $pluginFileExists) {
                    $tool = (object) $s->tool;
                    $runtime = $tool->metadata['runtime'] ?? 'nodejs';
                    return $runtime === $agentType && $pluginFileExists($tool->slug, null);
                })
                ->map(function ($s) {
                    $tool = (object) $s->tool;
                    return [
                        'tool_slug'      => $tool->slug,
                        'name'           => $tool->title,
                        'version'        => $tool->version ?? '1.0.0',
                        'download_url'   => url()->temporarySignedRoute('api.tools.plugin.download', now()->addHour(), ['slug' => $tool->slug]),
                        'is_subscribed'  => true,
                        'license_status' => $s->status,  // 'active' | 'expired' | 'suspended'
                        'expires_at'     => $s->expires_at?->toIso8601String(),
                    ];
                });

            // Slugs already covered by a paid subscription (avoid duplicates)
            $subscribedSlugs = $paidPlugins->pluck('tool_slug')->all();

            // ── 2. Free tools — no subscription needed ──────────────────────
            $freePlugins = collect(config('tools'))
                ->filter(fn($t) => ($t['is_active'] ?? false) && !in_array($t['slug'], $subscribedSlugs))
                ->map(fn($t) => (object) $t)
                ->values()
                ->filter(function ($tool) use ($agentType, $pluginFileExists) {
                    $runtime = $tool->metadata['runtime'] ?? 'nodejs';
                    return $runtime === $agentType && $pluginFileExists($tool->slug, null);
                })
                ->map(fn($tool) => [
                    'tool_slug'      => $tool->slug,
                    'name'           => $tool->title,
                    'version'        => $tool->version ?? '1.0.0',
                    'download_url'   => url()->temporarySignedRoute('api.tools.plugin.download', now()->addHour(), ['slug' => $tool->slug]),
                    'is_subscribed'  => false,
                    'license_status' => 'active', // free tools are always active
                    'expires_at'     => null,
                ]);

            $plugins = collect($paidPlugins->all())->merge($freePlugins->all())->values();

            return response()->json(['plugins' => $plugins]);
        })->name('agent.plugins');

    });

    // Signed plugin download (called by agent syncer)
    Route::get('/agent/plugins/{slug}/download', function (\Illuminate\Http\Request $request, string $slug) {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired download link.');
        }

        // Try downloading from the public/plugins folder first if it exists (.msp custom secure plugin first)
        $publicFilePathMsp = public_path("plugins/{$slug}.msp");
        if (file_exists($publicFilePathMsp)) {
            return response()->download($publicFilePathMsp, "{$slug}.msp");
        }

        $publicFilePathZip = public_path("plugins/{$slug}.zip");
        if (file_exists($publicFilePathZip)) {
            return response()->download($publicFilePathZip, "{$slug}.zip");
        }

        $toolConfig = collect(config('tools'))->firstWhere('slug', $slug);
        if (!$toolConfig) {
            abort(404, 'Plugin not found.');
        }
        $version = $toolConfig['latestVersion'] ?? null; // Adjust according to config setup if needed
        if (!$version || !$version->file_path || !\Illuminate\Support\Facades\Storage::exists($version->file_path)) {
            abort(404, 'Plugin file not found.');
        }
        return \Illuminate\Support\Facades\Storage::download($version->file_path);
    })->name('plugin.download');
});
