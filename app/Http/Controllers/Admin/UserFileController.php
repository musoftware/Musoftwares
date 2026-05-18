<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use ZipArchive;

/**
 * Admin file manager for platform users.
 * Recovered from old project: Admin/FileController.
 *
 * Architecture: Local disk storage scoped per user.
 * Files stored at: storage/app/user-files/{user_id}/{folder_path}/
 *
 * Features recovered:
 * - Upload with extension validation
 * - Folder hierarchy navigation
 * - Single file and multi-file zip download
 * - Folder zip download (recursive)
 * - Rename, move, delete
 */
class UserFileController extends Controller
{
    private const DISK = 'local';
    private const BASE = 'user-files';

    // Allowed upload extensions — recovered from old project: AdminFileExtension rule
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf',
        'zip', 'rar', '7z', 'tar',
        'mp4', 'mov', 'avi', 'mkv', 'mp3', 'wav', 'ogg',
        'json', 'xml', 'sql',
    ];

    /**
     * List files and folders at the given path (breadcrumb navigation).
     * Recovered from old project: FileController::load()
     */
    public function index(Request $request, int $userId)
    {
        $user   = User::findOrFail($userId);
        $folder = $this->sanitizePath($request->get('folder', ''));
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

        $breadcrumbs = $this->buildBreadcrumbs($folder);

        return Inertia::render('Admin/Users/Files', [
            'user'        => ['id' => $user->id, 'name' => $user->name],
            'files'       => $files->values(),
            'folders'     => $dirs->values(),
            'breadcrumbs' => $breadcrumbs,
            'current_folder' => $folder,
        ]);
    }

    /**
     * Upload a file for a user.
     * Recovered from old project: FileController::upload_direct()
     */
    public function upload(Request $request, int $userId)
    {
        $user   = User::findOrFail($userId);
        $folder = $this->sanitizePath($request->get('folder', ''));

        $request->validate([
            'file' => [
                'required', 'file', 'max:51200', // 50MB
                function ($attr, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
                        $fail("File type .{$ext} is not allowed.");
                    }
                },
            ],
        ]);

        $file    = $request->file('file');
        $ext     = strtolower($file->getClientOriginalExtension());
        // Store with UUID filename (no extension shown in storage = security)
        $storedName = Str::uuid() . '.' . $ext;
        $destPath   = self::BASE . '/' . $userId . ($folder ? '/' . $folder : '');

        Storage::disk(self::DISK)->putFileAs($destPath, $file, $storedName);

        return response()->json([
            'success'      => true,
            'original_name'=> $file->getClientOriginalName(),
            'stored_name'  => $storedName,
            'path'         => $destPath . '/' . $storedName,
        ]);
    }

    /**
     * Create a new folder.
     * Recovered from old project: FileController::new_folder()
     */
    public function newFolder(Request $request, int $userId)
    {
        $request->validate(['name' => 'required|string|max:100|alpha_dash']);
        User::findOrFail($userId);

        $parent = $this->sanitizePath($request->get('parent', ''));
        $path   = self::BASE . '/' . $userId . ($parent ? '/' . $parent : '') . '/' . $request->input('name');

        Storage::disk(self::DISK)->makeDirectory($path);

        return response()->json(['success' => true, 'path' => $path]);
    }

    /**
     * Download files — single file, multiple files as zip, or a folder as zip.
     * Recovered from old project: FileController::download_direct()
     */
    public function download(Request $request, int $userId)
    {
        User::findOrFail($userId);
        $paths = (array) $request->get('paths', []);

        if (count($paths) === 1) {
            $fullPath = storage_path('app/' . $paths[0]);
            if (is_file($fullPath)) {
                return response()->download($fullPath, basename($paths[0]));
            }
            // It's a folder — zip it
            return $this->zipFolder($fullPath, basename($paths[0]));
        }

        // Multiple files/folders — zip all
        $zipFile = tempnam(sys_get_temp_dir(), 'userfiles_') . '.zip';
        $zip     = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($paths as $relPath) {
            $fullPath = storage_path('app/' . $relPath);
            if (is_file($fullPath)) {
                $zip->addFile($fullPath, basename($fullPath));
            } elseif (is_dir($fullPath)) {
                $this->addFolderToZip($zip, $fullPath, basename($fullPath));
            }
        }
        $zip->close();

        return response()->download($zipFile, 'files.zip')->deleteFileAfterSend(true);
    }

    /**
     * Rename a file or folder.
     * Recovered from old project: FileController::rename()
     */
    public function rename(Request $request, int $userId)
    {
        $request->validate([
            'path'    => 'required|string',
            'new_name'=> 'required|string|max:255',
        ]);
        User::findOrFail($userId);

        $oldPath = $request->input('path');
        $dir     = dirname($oldPath);
        $newPath = $dir . '/' . $request->input('new_name');

        if (!str_starts_with($oldPath, self::BASE . '/' . $userId)) {
            abort(403);
        }

        Storage::disk(self::DISK)->move($oldPath, $newPath);

        return response()->json(['success' => true, 'new_path' => $newPath]);
    }

    /**
     * Move files/folders to a new location.
     * Recovered from old project: FileController::move()
     */
    public function move(Request $request, int $userId)
    {
        $request->validate([
            'paths'      => 'required|array',
            'destination'=> 'required|string',
        ]);
        User::findOrFail($userId);

        $base = self::BASE . '/' . $userId;
        foreach ($request->input('paths') as $path) {
            if (!str_starts_with($path, $base)) {
                abort(403);
            }
            $dest = $request->input('destination') . '/' . basename($path);
            Storage::disk(self::DISK)->move($path, $dest);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete files or folders.
     * Recovered from old project: FileController::delete()
     */
    public function delete(Request $request, int $userId)
    {
        $request->validate(['paths' => 'required|array']);
        User::findOrFail($userId);

        $base = self::BASE . '/' . $userId;
        foreach ($request->input('paths') as $path) {
            if (!str_starts_with($path, $base)) {
                abort(403);
            }
            $fullPath = storage_path('app/' . $path);
            if (is_dir($fullPath)) {
                Storage::disk(self::DISK)->deleteDirectory($path);
            } else {
                Storage::disk(self::DISK)->delete($path);
            }
        }

        return response()->json(['success' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function sanitizePath(string $path): string
    {
        // Prevent directory traversal
        return trim(str_replace(['..', '\\'], ['', '/'], $path), '/');
    }

    private function buildBreadcrumbs(string $folder): array
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

    private function zipFolder(string $folderPath, string $name): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'folder_') . '.zip';
        $zip     = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $this->addFolderToZip($zip, $folderPath, $name);
        $zip->close();
        return response()->download($zipFile, $name . '.zip')->deleteFileAfterSend(true);
    }
}
