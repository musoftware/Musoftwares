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
    public function index(Request $request, \App\Services\SubscriptionService $subscriptionService)
    {
        $agentType = $request->query('agent', 'nodejs'); // 'nodejs' or 'python'
        $user = auth()->user();

        // Fetch all tools from config
        $allTools = collect(config('tools'));

        // Filter tools the user has access to
        $plugins = $allTools->filter(function ($tool) use ($user, $agentType, $subscriptionService) {
            // Check runtime
            $runtime = data_get($tool, 'metadata.runtime', 'nodejs');
            if ($runtime !== $agentType) {
                return false;
            }

            // Use the centralized SubscriptionService which checks BOTH new UserSubscription and legacy ToolSubscription
            return $subscriptionService->hasAccessToTool($user, $tool['slug']);
        })->map(function ($tool) {
            return [
                'tool_slug'      => $tool['slug'],
                'name'           => $tool['title'],
                'version'        => $tool['version'] ?? '1.0.0',
                'download_url'   => url()->temporarySignedRoute('api.tools.plugin.download', now()->addHour(), ['slug' => $tool['slug']]),
                'is_subscribed'  => true,
                'license_status' => 'active', 
                'expires_at'     => null,
            ];
        })->values();

        return response()->json([
            'plugins' => $plugins,
        ]);
    }

    /**
     * Signed plugin download (called by agent syncer)
     */
    public function download(Request $request, string $slug)
    {
        if (!$request->hasValidSignature()) {
            abort(403, __('general.invalid_or_expired_download_link'));
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
            abort(404, __('general.plugin_not_found'));
        }
        $version = $toolConfig['latestVersion'] ?? null; // Adjust according to config setup if needed
        if (!$version || !$version->file_path || !Storage::exists($version->file_path)) {
            abort(404, __('general.plugin_file_not_found'));
        }
        return Storage::download($version->file_path);
    }
}
