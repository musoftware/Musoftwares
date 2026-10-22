<?php

namespace Modules\SoftwareStore\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Models\SoftwareRelease;
use Illuminate\Support\Facades\Storage;

/**
 * Serves the electron-updater "generic" provider feed for each program.
 */
class SoftwareUpdateFeedController extends Controller
{
    public function latest(string $slug)
    {
        $release = $this->product($slug)->latestRelease();
        abort_unless($release, 404);

        $name = rawurlencode($release->file_name);
        $yaml = implode("\n", [
            "version: {$release->version}",
            'files:',
            "  - url: {$name}",
            "    sha512: {$release->sha512}",
            "    size: {$release->size}",
            "path: {$name}",
            "sha512: {$release->sha512}",
            "releaseDate: '".$release->released_at?->toIso8601ZuluString()."'",
            '',
        ]);

        return response($yaml, 200, [
            'Content-Type' => 'text/yaml; charset=utf-8',
            'Cache-Control' => 'public, max-age=60',
        ]);
    }

    public function file(string $slug, string $file)
    {
        $release = $this->releaseForFile($this->product($slug), $file);
        abort_unless($release, 404);

        $isBlockmap = str_ends_with($file, '.blockmap');
        $path = $isBlockmap ? $release->blockmap_path : $release->file_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$file.'"',
        ]);
    }

    private function product(string $slug): SoftwareProduct
    {
        return SoftwareProduct::where('slug', $slug)->firstOrFail();
    }

    private function releaseForFile(SoftwareProduct $product, string $file): ?SoftwareRelease
    {
        $installerName = preg_replace('/\.blockmap$/', '', $file);

        return $product->releases()
            ->where('is_published', true)
            ->where('file_name', $installerName)
            ->first();
    }
}
