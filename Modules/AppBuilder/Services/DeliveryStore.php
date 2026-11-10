<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\AppBuilder\Models\BuilderProject;
use ZipArchive;

/**
 * Keeps each delivered version as a zip plus its unpacked files on the private local disk.
 * Nothing here is under public/, so the files are only reachable through the sandboxed preview route.
 */
class DeliveryStore
{
    private const MAX_UNPACKED_BYTES = 400 * 1024 * 1024;

    public const ENTRY_FILE = 'index.html';

    /** A React, Vue or Angular build with no backend opens from its built folder. */
    public const BUILT_ENTRY_FILE = 'dist/index.html';

    /** An Android app's web build, opened as the live preview (see the worker's android-apps skill). */
    public const APP_PREVIEW_FILE = 'preview/index.html';

    /** The signed Android app the customer installs. */
    public const APK_FILE = 'release/app-release.apk';

    /** A server program has no index.html; its SETUP.md says how to run it. */
    private const SETUP_FILE = 'SETUP.md';

    /** Hidden files a server program needs. A real .env (with secrets) is never one of them. */
    private const ALLOWED_DOT_FILES = ['.env.example', '.gitignore', '.htaccess', '.editorconfig'];

    private const MIME = [
        'html' => 'text/html; charset=UTF-8', 'htm' => 'text/html; charset=UTF-8', 'css' => 'text/css; charset=UTF-8',
        'js' => 'text/javascript; charset=UTF-8', 'mjs' => 'text/javascript; charset=UTF-8', 'json' => 'application/json',
        'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'txt' => 'text/plain; charset=UTF-8', 'md' => 'text/plain; charset=UTF-8', 'csv' => 'text/csv; charset=UTF-8',
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'mp4' => 'video/mp4', 'webm' => 'video/webm',
        'wasm' => 'application/wasm', 'apk' => 'application/vnd.android.package-archive',
    ];

    /** Unpacks a checked archive as the project's next version and returns that version number. */
    public function store(BuilderProject $project, string $zipPath): int
    {
        $zip = $this->open($zipPath);
        $entries = $this->checkedEntries($zip);
        $version = $project->version + 1;
        $disk = Storage::disk('local');

        foreach ($entries as $index => $name) {
            $disk->put($project->versionFolder($version).'/'.$name, (string) $zip->getFromIndex($index));
        }
        $zip->close();
        $disk->put($project->archivePath($version), (string) file_get_contents($zipPath));

        return $version;
    }

    /** Absolute path of a delivered file, or null when it does not exist or leaves the version folder. */
    public function file(BuilderProject $project, string $path): ?string
    {
        $path = $path === '' ? self::ENTRY_FILE : $path;
        if (! $this->isSafePath($path)) {
            return null;
        }

        $root = realpath(Storage::disk('local')->path($project->versionFolder()));
        $full = realpath(Storage::disk('local')->path($project->versionFolder().'/'.$path));

        return $root && $full && is_file($full) && str_starts_with($full, $root.DIRECTORY_SEPARATOR) ? $full : null;
    }

    public function mime(string $path): string
    {
        return self::MIME[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    }

    private function open(string $zipPath): ZipArchive
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            throw new InvalidArgumentException('The archive is not a valid zip file.');
        }

        return $zip;
    }

    /** @return array<int, string> zip index => safe relative path, files only */
    private function checkedEntries(ZipArchive $zip): array
    {
        if ($zip->numFiles > (int) config('builder.limits.archive_files')) {
            throw new InvalidArgumentException('The archive has too many files.');
        }

        $entries = [];
        $bytes = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = (array) $zip->statIndex($index);
            $name = (string) ($stat['name'] ?? '');
            if (str_ends_with($name, '/')) {
                continue;
            }
            if (! $this->isSafePath($name)) {
                throw new InvalidArgumentException('Unsafe file name in the archive: '.$name);
            }
            $bytes += (int) ($stat['size'] ?? 0);
            $entries[$index] = $name;
        }

        return $this->assertComplete($entries, $bytes);
    }

    private function assertComplete(array $entries, int $bytes): array
    {
        if ($bytes > self::MAX_UNPACKED_BYTES) {
            throw new InvalidArgumentException('The archive is too large once unpacked.');
        }
        if (! array_intersect([self::ENTRY_FILE, self::BUILT_ENTRY_FILE, self::SETUP_FILE], $entries)) {
            throw new InvalidArgumentException('The archive has no index.html or SETUP.md at its root.');
        }

        return $entries;
    }

    /** Relative, forward slashes, no "..", no hidden or empty parts. */
    private function isSafePath(string $path): bool
    {
        if (strlen($path) > 200 || ! preg_match('#^[A-Za-z0-9_\-./]+$#', $path) || str_starts_with($path, '/')) {
            return false;
        }

        foreach (explode('/', $path) as $part) {
            if ($part === '' || (str_starts_with($part, '.') && ! in_array($part, self::ALLOWED_DOT_FILES, true))) {
                return false;
            }
        }

        return true;
    }
}
