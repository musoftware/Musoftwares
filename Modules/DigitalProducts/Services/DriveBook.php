<?php

declare(strict_types=1);

namespace Modules\DigitalProducts\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Brings a book PDF from a Google Drive link, so the admin does not upload big files from the browser.
 * Only Drive file links are read: the download address is built from the file id, never taken from the link,
 * so the server cannot be pointed at any other address. The file waits in a private folder under a token
 * until the book is saved; files left there are removed after a day.
 */
final class DriveBook
{
    public const MAX_BYTES = 150 * 1024 * 1024;

    private const FOLDER = 'books/incoming';

    private const HOSTS = ['drive.google.com', 'docs.google.com', 'drive.usercontent.google.com'];

    private const TIMEOUT_SECONDS = 600;

    private const KEEP_SECONDS = 86400;

    public static function fileId(string $link): ?string
    {
        $parts = parse_url(trim($link));
        if (! is_array($parts) || ! in_array(strtolower($parts['host'] ?? ''), self::HOSTS, true)) {
            return null;
        }

        parse_str($parts['query'] ?? '', $query);
        $fromPath = preg_match('#/d/([A-Za-z0-9_-]{10,})#', $parts['path'] ?? '', $match) === 1 ? $match[1] : null;
        $id = $fromPath ?? (is_string($query['id'] ?? null) ? $query['id'] : null);

        return $id !== null && preg_match('/^[A-Za-z0-9_-]{10,}$/', $id) === 1 ? $id : null;
    }

    /**
     * @return array{token: string, name: string, size: int}
     *
     * @throws RuntimeException with a message for the admin
     */
    public function fetch(string $link): array
    {
        $id = self::fileId($link) ?? throw new RuntimeException(__('admin.digital_products_drive_bad_link'));
        $this->removeOld();
        $token = (string) Str::uuid();
        $path = $this->pathFor($token);
        File::ensureDirectoryExists(dirname($path));

        $name = $this->download($id, $path);
        $this->checkPdf($path);

        return ['token' => $token, 'name' => $name, 'size' => (int) filesize($path)];
    }

    public function path(string $token): ?string
    {
        if (! Str::isUuid($token)) {
            return null;
        }

        $path = $this->pathFor($token);

        return is_file($path) ? $path : null;
    }

    public function asUpload(string $token): ?UploadedFile
    {
        $path = $this->path($token);

        return $path === null ? null : new UploadedFile($path, 'book.pdf', 'application/pdf', null, true);
    }

    public function forget(string $token): void
    {
        $path = $this->path($token);
        if ($path !== null) {
            File::delete($path);
        }
    }

    private function download(string $id, string $path): string
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withOptions(['sink' => $path, 'progress' => $this->stopWhenTooBig(...)])
                ->get('https://drive.usercontent.google.com/download', ['id' => $id, 'export' => 'download', 'confirm' => 't']);
        } catch (Throwable $e) {
            File::delete($path);
            Log::warning('Drive book download failed', ['drive_id' => $id, 'error' => $e->getMessage()]);
            throw new RuntimeException(str_contains($e->getMessage(), 'too big') ? __('admin.digital_products_drive_too_big') : __('admin.digital_products_drive_failed'));
        }

        if (! $response->successful()) {
            File::delete($path);
            throw new RuntimeException(__('admin.digital_products_drive_not_shared'));
        }

        return self::fileName((string) $response->header('Content-Disposition'));
    }

    private function stopWhenTooBig(int $total, int $downloaded): void
    {
        if ($total > self::MAX_BYTES || $downloaded > self::MAX_BYTES) {
            throw new RuntimeException('too big');
        }
    }

    private function checkPdf(string $path): void
    {
        $head = (string) file_get_contents($path, false, null, 0, 5);
        $error = match (true) {
            filesize($path) > self::MAX_BYTES => __('admin.digital_products_drive_too_big'),
            $head !== '%PDF-' => __('admin.digital_products_drive_not_shared'),
            default => null,
        };

        if ($error !== null) {
            File::delete($path);
            throw new RuntimeException($error);
        }
    }

    public static function fileName(string $disposition): string
    {
        if (preg_match("/filename\*=UTF-8''([^;]+)/i", $disposition, $match) === 1) {
            return rawurldecode(trim($match[1]));
        }

        return preg_match('/filename="?([^";]+)"?/i', $disposition, $match) === 1 ? trim($match[1]) : 'book.pdf';
    }

    private function pathFor(string $token): string
    {
        return Storage::disk('local')->path(self::FOLDER.'/'.$token.'.pdf');
    }

    private function removeOld(): void
    {
        foreach (File::glob(Storage::disk('local')->path(self::FOLDER.'/*.pdf')) as $old) {
            if (filemtime($old) < time() - self::KEEP_SECONDS) {
                File::delete($old);
            }
        }
    }
}
