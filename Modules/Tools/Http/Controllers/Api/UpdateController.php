<?php

namespace Modules\Tools\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tools\Models\Tool;

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
        $tool    = Tool::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $latest  = $tool->latestVersion;

        if (!$latest) {
            return response()->json(['update_available' => false]);
        }

        $currentVersion = $request->query('current_version', '0.0.0');
        $hasUpdate      = version_compare($latest->version, $currentVersion, '>');

        if (!$hasUpdate) {
            return response()->json([
                'update_available' => false,
                'current_version'  => $currentVersion,
                'latest_version'   => $latest->version,
            ]);
        }

        // Build a signed 1-hour download URL for the updater
        $downloadUrl = $latest->file_path
            ? url()->temporarySignedRoute(
                'tools.download.serve',
                now()->addHour(),
                ['slug' => $slug, 'version_id' => $latest->id]
            )
            : null;

        return response()->json([
            'update_available' => true,
            'latest_version'   => $latest->version,
            'current_version'  => $currentVersion,
            'changelog'        => $latest->changelog,
            'file_size'        => $latest->formatted_size,
            'checksum'         => $latest->checksum,
            'download_url'     => $downloadUrl,
            'released_at'      => $latest->released_at?->toDateString(),
        ]);
    }

    /**
     * GET /api/tools/{slug}/releases
     * Full version history for the release notes panel in the desktop app.
     */
    public function releases(string $slug): JsonResponse
    {
        $tool = Tool::where('slug', $slug)->firstOrFail();

        $versions = $tool->versions()
            ->orderByDesc('released_at')
            ->limit(20)
            ->get()
            ->map(fn($v) => [
                'version'     => $v->version,
                'changelog'   => $v->changelog,
                'is_latest'   => $v->is_latest,
                'is_beta'     => $v->is_beta,
                'file_size'   => $v->formatted_size,
                'released_at' => $v->released_at?->toDateString(),
            ]);

        return response()->json(['versions' => $versions]);
    }
}
