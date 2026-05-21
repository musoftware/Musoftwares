<?php

namespace Modules\Tools\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


/**
 * Called by Python desktop app updater at startup and periodically.
 * Returns latest version info and a signed download URL.
 */
class UpdateController extends Controller
{
    /**
     * GET /api/tools/{slug}/update-check?current_version=1.0.0
     */
    public function check(Request $request, string $slug): JsonResponse
    {
        $tool = collect(config('tools'))->firstWhere('slug', $slug);

        if (!$tool || !($tool['is_active'] ?? false)) {
            return response()->json(['update_available' => false]);
        }

        $latestVersion = $tool['version'] ?? '1.0.0';
        $currentVersion = $request->query('current_version', '0.0.0');
        $hasUpdate      = version_compare($latestVersion, $currentVersion, '>');

        if (!$hasUpdate) {
            return response()->json([
                'update_available' => false,
                'current_version'  => $currentVersion,
                'latest_version'   => $latestVersion,
            ]);
        }

        // Build a signed 1-hour download URL for the updater
        $downloadUrl = url()->temporarySignedRoute(
            'api.tools.plugin.download',
            now()->addHour(),
            ['slug' => $slug]
        );

        return response()->json([
            'update_available' => true,
            'latest_version'   => $latestVersion,
            'current_version'  => $currentVersion,
            'changelog'        => null,
            'file_size'        => '0MB',
            'checksum'         => null,
            'download_url'     => $downloadUrl,
            'released_at'      => now()->toDateString(),
        ]);
    }

    /**
     * GET /api/tools/{slug}/releases
     * Full version history for the release notes panel in the desktop app.
     */
    public function releases(string $slug): JsonResponse
    {
        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        
        if (!$tool) {
            abort(404);
        }

        $versions = [
            [
                'version'     => $tool['version'] ?? '1.0.0',
                'changelog'   => null,
                'is_latest'   => true,
                'is_beta'     => false,
                'file_size'   => '0MB',
                'released_at' => now()->toDateString(),
            ]
        ];

        return response()->json(['versions' => $versions]);
    }
}
