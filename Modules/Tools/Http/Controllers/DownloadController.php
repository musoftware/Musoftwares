<?php

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tools\Models\Tool;
use Modules\Tools\Models\ToolDownload;
use Modules\Tools\Models\ToolLicense;
use Modules\Tools\Models\ToolSubscription;
use Modules\Tools\Models\ToolVersion;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Download history page — all user downloads.
     */
    public function index(): Response
    {
        $downloads = ToolDownload::where('user_id', auth()->id())
            ->with(['tool:id,slug,title,icon', 'version:id,version,released_at'])
            ->latest()
            ->paginate(20)
            ->through(fn($d) => [
                'id'           => $d->id,
                'tool'         => [
                    'slug'     => $d->tool?->slug,
                    'title'    => $d->tool?->title,
                    'icon_url' => $d->tool?->icon_url,
                ],
                'version'      => $d->version?->version ?? 'N/A',
                'downloaded_at' => $d->downloaded_at?->diffForHumans() ?? $d->created_at?->diffForHumans() ?? '-',
            ]);

        // Available downloads (tools user is subscribed to)
        $availableTools = ToolSubscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['tool.latestVersion'])
            ->get()
            ->filter(fn($s) => $s->tool?->latestVersion)
            ->map(fn($s) => [
                'tool_slug'       => $s->tool->slug,
                'tool_title'      => $s->tool->title,
                'tool_icon_url'   => $s->tool->icon_url,
                'version'         => $s->tool->latestVersion->version,
                'file_size'       => $s->tool->latestVersion->formatted_size,
                'released_at'     => $s->tool->latestVersion->released_at?->toDateString(),
            ]);

        return Inertia::render('Tools/Downloads', [
            'downloads'      => $downloads,
            'availableTools' => $availableTools->values(),
        ]);
    }

    /**
     * Generate a signed download token and redirect.
     * The token is short-lived (15 minutes) and single-use in spirit.
     */
    public function generate(Request $request, string $slug): RedirectResponse
    {
        $tool    = Tool::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $version = $tool->latestVersion;

        if (!$version) {
            return back()->with('error', 'No downloadable version is available yet.');
        }

        // Verify active subscription or valid license
        $hasAccess = ToolSubscription::where('user_id', auth()->id())
            ->where('tool_id', $tool->id)
            ->where('status', 'active')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

        if (!$hasAccess) {
            return back()->with('error', 'You need an active subscription to download this tool.');
        }

        // Build signed URL (15 min TTL)
        $signedUrl = url()->temporarySignedRoute(
            'tools.download.serve',
            now()->addMinutes(15),
            ['slug' => $slug, 'version_id' => $version->id]
        );

        return redirect($signedUrl);
    }

    /**
     * Serve the file after validating signature.
     */
    public function serve(Request $request, string $slug, int $versionId): StreamedResponse|RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Download link has expired. Please generate a new one.');
        }

        $tool    = Tool::where('slug', $slug)->firstOrFail();
        $version = ToolVersion::where('tool_id', $tool->id)->findOrFail($versionId);

        if (!$version->file_path || !Storage::exists($version->file_path)) {
            return back()->with('error', 'File not found on server. Please contact support.');
        }

        // Log the download
        ToolDownload::create([
            'user_id'         => auth()->id(),
            'tool_id'         => $tool->id,
            'tool_version_id' => $version->id,
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'downloaded_at'   => now(),
        ]);

        $tool->incrementDownloads();

        $fileName = $version->file_name ?? Str::slug($tool->title) . '-v' . $version->version . '.zip';

        return Storage::download($version->file_path, $fileName);
    }
}
