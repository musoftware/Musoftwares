<?php

namespace Modules\AppBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\DeliveryStore;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BuilderDeliveryController extends Controller
{
    /**
     * "sandbox" without allow-same-origin gives the program its own blank origin even when opened directly,
     * so its scripts can never read this site's cookies or call this site as the visitor.
     */
    private const SANDBOX_POLICY = "sandbox allow-scripts allow-forms allow-modals allow-popups allow-downloads; default-src 'self' data: blob: 'unsafe-inline' 'unsafe-eval'; connect-src 'none'; frame-ancestors 'self'";

    /**
     * An Android app's web build loads its own engine and files with fetch, so it may read files of this delivery
     * (connect-src 'self'; the sandboxed page has no origin, hence the CORS header). It still has no cookies.
     */
    private const APP_PREVIEW_POLICY = "sandbox allow-scripts allow-forms allow-modals allow-popups allow-downloads; default-src 'self' data: blob: 'unsafe-inline' 'unsafe-eval' 'wasm-unsafe-eval'; connect-src 'self'; frame-ancestors 'self'";

    /** Source and project files: handed over in the download once fully paid, never through the preview before that. */
    private const SOURCE_EXTENSIONS = ['php', 'ts', 'tsx', 'jsx', 'vue', 'svelte', 'py', 'rb', 'go', 'java', 'kt', 'cs', 'dart', 'rs', 'swift', 'c', 'cpp', 'h',
        'sql', 'env', 'lock', 'md', 'yml', 'yaml', 'toml', 'ini', 'sh', 'bat', 'ps1', 'gradle', 'properties', 'map', 'twig', 'example', 'conf', 'keystore', 'jks'];

    /** Folders that hold a server program's code, wherever they sit (dist/server too). */
    private const SOURCE_FOLDERS = ['src', 'app', 'routes', 'database', 'migrations', 'config', 'server', 'backend', 'tests', 'vendor', 'node_modules', 'deploy', 'bootstrap', 'resources', 'storage', 'android', 'ios', 'lib'];

    public function __construct(private readonly DeliveryStore $store) {}

    public function preview(BuilderProject $project, string $path = ''): BinaryFileResponse|RedirectResponse
    {
        // relative links like css/site.css only resolve inside /app/ when the page itself sits under /app/
        if ($path === '') {
            return redirect()->route('builder.preview', ['project' => $project, 'path' => DeliveryStore::ENTRY_FILE]);
        }

        $file = $project->hasDelivery() ? $this->store->file($project, $path) : null;
        abort_unless($file, 404);
        // the app itself and its signing key are the delivery, handed over with the rest of the files
        abort_if($project->hasBalanceDue() && $this->lockedUntilPaid($path), 402, __('builder.balance_locked'));

        $appPreview = str_starts_with($path, 'preview/');
        // the app files (an APK, a Windows program, its installer) are saved, never opened in the browser
        $saved = str_starts_with($path, 'release/') ? ['Content-Disposition' => 'attachment; filename="'.basename($file).'"'] : [];

        return response()->file($file, $saved + [
            'Content-Type' => $this->store->mime($file),
            'Content-Security-Policy' => $appPreview ? self::APP_PREVIEW_POLICY : self::SANDBOX_POLICY,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=60',
            'X-Robots-Tag' => 'noindex',
        ] + ($appPreview ? ['Access-Control-Allow-Origin' => '*'] : []));
    }

    /**
     * After a deposit the preview shows the program, but the source stays locked until the rest is paid: the app
     * files in release/, server code and project files. A phone app's preview/ build is the preview itself.
     */
    private function lockedUntilPaid(string $path): bool
    {
        $path = strtolower($path);
        if (str_starts_with($path, 'release/')) {
            return true;
        }
        if (str_starts_with($path, 'preview/')) {
            return false;
        }
        $folders = array_slice(explode('/', $path), 0, -1);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return array_intersect($folders, self::SOURCE_FOLDERS) !== []
            || in_array($extension, self::SOURCE_EXTENSIONS, true)
            // package.json, composer.json and the like describe the project; a built site's own .json lives in dist/ or assets/
            || ($extension === 'json' && array_intersect($folders, ['dist', 'assets', 'build', 'public', 'data']) === []);
    }

    public function download(BuilderProject $project): StreamedResponse
    {
        abort_unless($project->hasDelivery() && Storage::disk('local')->exists($project->archivePath()), 404);
        // after a deposit, the files are handed over when the rest is paid; the preview stays open
        abort_if($project->hasBalanceDue(), 402, __('builder.balance_locked'));

        return Storage::disk('local')->download($project->archivePath(), 'program-'.$project->version.'.zip');
    }
}
