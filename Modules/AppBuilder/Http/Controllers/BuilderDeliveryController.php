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

    public function __construct(private readonly DeliveryStore $store) {}

    public function preview(BuilderProject $project, string $path = ''): BinaryFileResponse|RedirectResponse
    {
        // relative links like css/site.css only resolve inside /app/ when the page itself sits under /app/
        if ($path === '') {
            return redirect()->route('builder.preview', ['project' => $project, 'path' => DeliveryStore::ENTRY_FILE]);
        }

        $file = $project->hasDelivery() ? $this->store->file($project, $path) : null;
        abort_unless($file, 404);

        return response()->file($file, [
            'Content-Type' => $this->store->mime($file),
            'Content-Security-Policy' => self::SANDBOX_POLICY,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=60',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public function download(BuilderProject $project): StreamedResponse
    {
        abort_unless($project->hasDelivery() && Storage::disk('local')->exists($project->archivePath()), 404);
        // after a deposit, the files are handed over when the rest is paid; the preview stays open
        abort_if($project->hasBalanceDue(), 402, __('builder.balance_locked'));

        return Storage::disk('local')->download($project->archivePath(), 'program-'.$project->version.'.zip');
    }
}
