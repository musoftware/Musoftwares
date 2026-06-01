<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class UserFileService
{
    private const DISK = 'local';
    private const BASE = 'user-files';

    public function getFilesAndFolders(int $userId, string $folder): array
    {
        $folder = $this->sanitizePath($folder);
        $base   = self::BASE . '/' . $userId . ($folder ? '/' . $folder : '');

        $allFiles = Storage::disk(self::DISK)->files($base);
        $allDirs  = Storage::disk(self::DISK)->directories($base);

        $files = collect($allFiles)->map(fn($path) => [
            'name'     => basename($path),
            'path'     => $path,
            'size'     => Storage::disk(self::DISK)->size($path),
            'type'     => 'file',
            'ext'      => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            'modified' => Storage::disk(self::DISK)->lastModified($path),
        ]);

        $dirs = collect($allDirs)->map(fn($path) => [
            'name'  => basename($path),
            'path'  => $path,
            'type'  => 'folder',
        ]);

        return [
            'files'   => $files->values(),
            'folders' => $dirs->values(),
        ];
    }

    public function uploadFile(int $userId, UploadedFile $file, string $folder): array
    {
        $folder = $this->sanitizePath($folder);
        $ext    = strtolower($file->getClientOriginalExtension());
        
        $storedName = Str::uuid() . '.' . $ext;
        $destPath   = self::BASE . '/' . $userId . ($folder ? '/' . $folder : '');

        Storage::disk(self::DISK)->putFileAs($destPath, $file, $storedName);

        return [
            'original_name' => $file->getClientOriginalName(),
            'stored_name'   => $storedName,
            'path'          => $destPath . '/' . $storedName,
        ];
    }

    public function createFolder(int $userId, string $name, string $parentFolder): string
    {
        $parent = $this->sanitizePath($parentFolder);
        $path   = self::BASE . '/' . $userId . ($parent ? '/' . $parent : '') . '/' . $name;

        Storage::disk(self::DISK)->makeDirectory($path);

        return $path;
    }

    public function rename(int $userId, string $oldPath, string $newName): string
    {
        $dir     = dirname($oldPath);
        $newPath = $dir . '/' . $newName;

        if (!str_starts_with($oldPath, self::BASE . '/' . $userId)) {
            abort(403);
        }

        Storage::disk(self::DISK)->move($oldPath, $newPath);

        return $newPath;
    }

    public function move(int $userId, array $paths, string $destination): void
    {
        $base = self::BASE . '/' . $userId;
        foreach ($paths as $path) {
            if (!str_starts_with($path, $base)) {
                abort(403);
            }
            $dest = $destination . '/' . basename($path);
            Storage::disk(self::DISK)->move($path, $dest);
        }
    }

    public function delete(int $userId, array $paths): void
    {
        $base = self::BASE . '/' . $userId;
        foreach ($paths as $path) {
            if (!str_starts_with($path, $base)) {
                abort(403);
            }
            $fullPath = Storage::disk(self::DISK)->path($path);
            if (is_dir($fullPath)) {
                Storage::disk(self::DISK)->deleteDirectory($path);
            } else {
                Storage::disk(self::DISK)->delete($path);
            }
        }
    }

    public function download(int $userId, array $paths): BinaryFileResponse
    {
        if (count($paths) === 1) {
            $fullPath = Storage::disk(self::DISK)->path($paths[0]);
            if (is_file($fullPath)) {
                return response()->download($fullPath, basename($paths[0]));
            }
            return $this->zipFolder($fullPath, basename($paths[0]));
        }

        $zipFile = tempnam(sys_get_temp_dir(), 'userfiles_') . '.zip';
        $zip     = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($paths as $relPath) {
            $fullPath = Storage::disk(self::DISK)->path($relPath);
            if (is_file($fullPath)) {
                $zip->addFile($fullPath, basename($fullPath));
            } elseif (is_dir($fullPath)) {
                $this->addFolderToZip($zip, $fullPath, basename($fullPath));
            }
        }
        $zip->close();

        return response()->download($zipFile, 'files.zip')->deleteFileAfterSend(true);
    }

    public function buildBreadcrumbs(string $folder): array
    {
        if (empty($folder)) {
            return [['name' => 'Root', 'path' => '']];
        }
        $crumbs = [['name' => 'Root', 'path' => '']];
        $parts  = explode('/', $folder);
        $built  = '';
        foreach ($parts as $part) {
            $built = $built ? $built . '/' . $part : $part;
            $crumbs[] = ['name' => $part, 'path' => $built];
        }
        return $crumbs;
    }

    private function sanitizePath(string $path): string
    {
        return trim(str_replace(['..', '\\'], ['', '/'], $path), '/');
    }

    private function addFolderToZip(ZipArchive $zip, string $folderPath, string $zipPath): void
    {
        $zip->addEmptyDir($zipPath);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            $relativePath = $zipPath . '/' . ltrim(str_replace($folderPath, '', $file->getPathname()), '/\\');
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    private function zipFolder(string $folderPath, string $name): BinaryFileResponse
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'folder_') . '.zip';
        $zip     = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $this->addFolderToZip($zip, $folderPath, $name);
        $zip->close();
        return response()->download($zipFile, $name . '.zip')->deleteFileAfterSend(true);
    }
}
