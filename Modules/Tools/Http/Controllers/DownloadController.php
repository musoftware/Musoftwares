<?php

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

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
        // Models removed, using empty for now or basic mock
        $downloads = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

        // Available tools (subscribed and active)
        // Available tools (subscribed and active)
        $availableTools = \Modules\Tools\Models\ToolSubscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->get()
            ->map(function($s) {
                $tool = collect(config('tools'))->firstWhere('guid', $s->tool_guid);
                if (!$tool) return null;
                return [
                    'tool_slug'       => $tool['slug'],
                    'tool_title'      => $tool['title'],
                    'tool_icon_url'   => $tool['icon_url'] ?? null,
                    'version'         => $tool['version'] ?? '1.0.0',
                    'file_size'       => '0MB',
                    'released_at'     => now()->toDateString(),
                    'is_web_tool'     => true,
                ];
            })->filter();

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
        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        
        if (!$tool || !($tool['is_active'] ?? false)) {
            abort(404);
        }

        // Verify active subscription
        $hasAccess = \Modules\Tools\Models\ToolSubscription::where('user_id', auth()->id())
            ->where('tool_guid', $tool['guid'])
            ->where('status', 'active')
            ->exists();

        if (!$hasAccess) {
            return back()->with('error', __('general.you_need_an_active_subscription_to_download_this_tool'));
        }

        // Build signed URL (15 min TTL)
        $signedUrl = url()->temporarySignedRoute(
            'tools.download.serve',
            now()->addMinutes(15),
            ['slug' => $slug, 'version_id' => 1]
        );

        return redirect($signedUrl);
    }

    /**
     * Serve the file after validating signature.
     */
    public function serve(Request $request, string $slug, int $versionId): StreamedResponse|RedirectResponse
    {
        if (!$request->hasValidSignature()) {
            abort(403, __('general.download_link_has_expired_please_generate_a_new_one'));
        }

        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        if (!$tool) {
            abort(404);
        }

        $fileName = Str::slug($tool['title']) . '-v' . ($tool['version'] ?? '1.0.0') . '.zip';
        $publicFilePathMsp = public_path("plugins/{$slug}.msp");
        if (file_exists($publicFilePathMsp)) {
            return response()->download($publicFilePathMsp, "{$slug}.msp");
        }

        $publicFilePathZip = public_path("plugins/{$slug}.zip");
        if (file_exists($publicFilePathZip)) {
            return response()->download($publicFilePathZip, "{$slug}.zip");
        }

        return back()->with('error', __('general.file_not_found_on_server_please_contact_support'));
    }

    /**
     * Download the local runtime agent installer.
     * Agents are stored at: storage/app/agents/{type}/musoftware-agent-{type}-setup.exe
     *
     * GET /tools/agent/download/{type}
     * Requires auth.
     */
    public function downloadAgent(Request $request, string $type): StreamedResponse|RedirectResponse|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless(in_array($type, ['node', 'python', 'extension']), 404);

        if ($type === 'extension') {
            $publicFilePath = public_path('downloads/musoftware-browser-runtime.zip');
            if (file_exists($publicFilePath)) {
                return response()->download($publicFilePath, 'musoftware-browser-runtime.zip');
            }
            abort(404, __('general.extension_build_not_found_please_contact_support'));
        }

        $platform = match(true) {
            str_contains($request->userAgent() ?? '', 'Windows') => 'win',
            str_contains($request->userAgent() ?? '', 'Mac')     => 'mac',
            default                                               => 'linux',
        };

        // If type is node, try to serve the compiled runtime package from public downloads folder first
        if ($type === 'node') {
            $platformDir = match($platform) {
                'win'   => 'windows',
                'mac'   => 'macos',
                default => 'linux',
            };
            $ext = $platform === 'win' ? '.exe' : '';
            $runtimeFileName = "musoftware-runtime-{$platform}{$ext}";
            $publicFilePath = public_path("downloads/runtime/{$platformDir}/{$runtimeFileName}");

            if (file_exists($publicFilePath)) {
                return response()->download($publicFilePath, $runtimeFileName);
            }
        }

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

