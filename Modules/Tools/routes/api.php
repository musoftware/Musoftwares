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
        // Polled by local agents to get list of subscribed plugins to auto-download
        Route::get('/agent/plugins', function (\Illuminate\Http\Request $request) {
            $agentType = $request->query('agent', 'nodejs'); // 'nodejs' or 'python'

            // Get user's active subscriptions with their tools
            $subscriptions = \Modules\Tools\Models\ToolSubscription::where('user_id', auth()->id())
                ->where('status', 'active')
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->with(['tool.latestVersion'])
                ->get();

            $plugins = $subscriptions
                ->filter(fn($s) => $s->tool && $s->tool->latestVersion)
                ->filter(function ($s) use ($agentType) {
                    // Filter by agent type (tools declare their runtime in metadata)
                    $runtime = $s->tool->metadata['runtime'] ?? 'nodejs';
                    return $runtime === $agentType;
                })
                ->map(fn($s) => [
                    'tool_slug'     => $s->tool->slug,
                    'name'          => $s->tool->title,
                    'version'       => $s->tool->latestVersion->version,
                    'download_url'  => $s->tool->latestVersion->file_path
                        ? url()->temporarySignedRoute('api.tools.plugin.download', now()->addHour(), ['slug' => $s->tool->slug])
                        : null,
                    'is_subscribed' => true,
                ])
                ->values();

            return response()->json(['plugins' => $plugins]);
        })->name('agent.plugins');

        // Signed plugin download (called by agent syncer)
        Route::get('/agent/plugins/{slug}/download', function (\Illuminate\Http\Request $request, string $slug) {
            if (!$request->hasValidSignature()) {
                abort(403, 'Invalid or expired download link.');
            }
            $tool    = \Modules\Tools\Models\Tool::where('slug', $slug)->firstOrFail();
            $version = $tool->latestVersion;
            if (!$version || !$version->file_path || !\Illuminate\Support\Facades\Storage::exists($version->file_path)) {
                abort(404, 'Plugin file not found.');
            }
            return \Illuminate\Support\Facades\Storage::download($version->file_path);
        })->name('plugin.download');
    });
});
