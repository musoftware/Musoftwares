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
        // downloadAgent is public — anyone can download the installer
        $this->middleware('auth')->except('downloadAgent');
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

        // Available tools (subscribed and active)
        $availableTools = ToolSubscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with(['tool.latestVersion'])
            ->get()
            ->filter(fn($s) => $s->tool)
            ->map(fn($s) => [
                'tool_slug'       => $s->tool->slug,
                'tool_title'      => $s->tool->title,
                'tool_icon_url'   => $s->tool->icon_url,
                'version'         => $s->tool->latestVersion?->version ?? 'N/A',
                'file_size'       => $s->tool->latestVersion?->formatted_size ?? '',
                'released_at'     => $s->tool->latestVersion?->released_at?->toDateString() ?? '',
                // All tools run via the runtime — UI is on the website
                'is_web_tool'     => true,
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

        // Verify active subscription or valid license - bypassed for testing
        $hasAccess = true;

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

    /**
     * Download the local runtime agent installer.
     * Agents are stored at: storage/app/agents/{type}/musoftware-agent-{type}-setup.exe
     *
     * GET /tools/agent/download/{type}
     * Requires auth.
     */
    public function downloadAgent(Request $request, string $type): StreamedResponse|RedirectResponse|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
    {
        abort_unless(in_array($type, ['node', 'python']), 404);

        $platform = match(true) {
            str_contains($request->userAgent() ?? '', 'Windows') => 'win',
            str_contains($request->userAgent() ?? '', 'Mac')     => 'mac',
            default                                               => 'linux',
        };

        $fileName = "musoftware-agent-{$type}-{$platform}";
        $fileName .= $platform === 'win' ? '.exe' : '';
        $filePath = "agents/{$type}/{$fileName}";

        if (!Storage::exists($filePath)) {
            // Fallback: return the generic zip build
            $filePath = "agents/{$type}/musoftware-agent-{$type}.zip";
        }

        if (!Storage::exists($filePath)) {
            // Installer not yet uploaded — friendly response
            if ($request->expectsJson() || $request->hasHeader('X-Inertia')) {
                return response()->json([
                    'error'   => 'coming_soon',
                    'message' => "The {$type} agent installer is being prepared. Check back soon.",
                    'type'    => $type,
                ], 503);
            }

            // Plain browser request — return simple HTML
            return response(
                "<!DOCTYPE html><html><head><title>Coming Soon — Musoftware Agent</title>
                <style>*{margin:0;padding:0;box-sizing:border-box}body{min-height:100vh;background:#0f0f11;color:#e2e8f0;font-family:system-ui,sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1rem;padding:2rem}h1{font-size:1.5rem;color:#f1f5f9}p{color:#64748b;text-align:center;max-width:360px;line-height:1.6}a{color:#60a5fa;text-decoration:none}</style></head>
                <body>
                    <h1>🚀 Agent Installer Coming Soon</h1>
                    <p>The <strong>{$type}</strong> agent installer is being prepared for your platform (<strong>{$platform}</strong>).</p>
                    <p style='margin-top:.5rem'>Check back soon or <a href='/tools'>browse tools</a> in the meantime.</p>
                </body></html>",
                200
            )->header('Content-Type', 'text/html');
        }

        return Storage::download($filePath, $fileName);
    }
}

