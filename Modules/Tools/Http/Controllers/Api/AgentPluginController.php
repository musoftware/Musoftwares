<?php

namespace Modules\Tools\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Tools\Models\ToolSubscription;

class AgentPluginController extends Controller
{
    /**
     * Polled by local agents to get list of subscribed plugins to auto-download.
     * Free tools (is_free=true) are automatically included for every authenticated user.
     */
    public function index(Request $request)
    {
        $agentType = $request->query('agent', 'nodejs'); // 'nodejs' or 'python'

        // Helper to check if a physical plugin file exists on the server
        $pluginFileExists = function (string $slug, $latestVersion) {
            if (file_exists(public_path("plugins/{$slug}.msp"))) {
                return true;
            }
            if (file_exists(public_path("plugins/{$slug}.zip"))) {
                return true;
            }
            if ($latestVersion && $latestVersion->file_path && Storage::exists($latestVersion->file_path)) {
                return true;
            }
            return false;
        };

        // ── 1. Subscription-based plugins ──────────────────────────────
        $subscriptions = ToolSubscription::where('user_id', auth()->id())
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
        // REMOVED: Do not auto-download all free plugins to save space and increase security.
        // Users must explicitly subscribe to a tool to get it downloaded.
        
        $plugins = collect($paidPlugins->all())->values();

        return response()->json(['plugins' => $plugins]);
    }

    /**
     * Signed plugin download (called by agent syncer)
     */
    public function download(Request $request, string $slug)
    {
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
        if (!$version || !$version->file_path || !Storage::exists($version->file_path)) {
            abort(404, 'Plugin file not found.');
        }
        return Storage::download($version->file_path);
    }
}
