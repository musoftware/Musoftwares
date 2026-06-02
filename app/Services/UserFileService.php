<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class UserFileService
{
    private const DISK = 'uploaded_user_files';

    public function getFilesAndFolders(int $userId, string $folderId): array
    {
        $folderId = str_replace('folder_', '', $folderId);
        $folderId = $folderId === '' ? null : (int)$folderId;

        $dbFiles = File::where('user_id', $userId)->where('folder_id', $folderId)->get();
        $dbFolders = FileFolder::where('user_id', $userId)->where('folder_id', $folderId)->get();

        $files = $dbFiles->map(function ($file) {
            $ext = strtolower(pathinfo($file->original_filename, PATHINFO_EXTENSION));
            return [
                'id'       => (string)$file->id,
                'name'     => $file->original_filename,
                'path'     => 'file_' . $file->id, // Prefix to avoid collision
                'size'     => $file->size,
                'type'     => 'file',
                'ext'      => $ext,
                'modified' => $file->updated_at->timestamp,
            ];
        });

        $folders = $dbFolders->map(function ($folder) {
            return [
                'id'    => (string)$folder->id,
                'name'  => $folder->foldername,
                'path'  => 'folder_' . $folder->id, // Prefix to avoid collision
                'type'  => 'folder',
            ];
        });

        return [
            'files'   => $files->values()->toArray(),
            'folders' => $folders->values()->toArray(),
        ];
    }

    public function uploadFile(int $userId, UploadedFile $file, string $folderId): array
    {
        $folderId = str_replace('folder_', '', $folderId);
        $folderId = $folderId === '' ? null : (int)$folderId;
        $ext = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid() . '.' . $ext;

        // Store file physically in the legacy disk 'uploaded_user_files'
        Storage::disk(self::DISK)->putFileAs('', $file, $storedName);

        // Determine filetype
        $filetype = 'document';
        if (in_array($ext, ['mp3'])) {
            $filetype = 'audio';
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $filetype = 'image';
        } elseif (in_array($ext, ['mp4', 'mov'])) {
            $filetype = 'video';
        } elseif (in_array($ext, ['json'])) {
            $filetype = 'json';
        }

        $dbFile = new File();
        $dbFile->user_id = $userId;
        $dbFile->folder_id = $folderId;
        $dbFile->filename = $storedName;
        $dbFile->original_filename = $file->getClientOriginalName();
        $dbFile->filetype = $filetype;
        $dbFile->url = Storage::disk(self::DISK)->url($storedName);
        $dbFile->size = $file->getSize();
        $dbFile->save();

        return [
            'original_name' => $dbFile->original_filename,
            'stored_name'   => $dbFile->filename,
            'path'          => 'file_' . $dbFile->id,
        ];
    }

    public function createFolder(int $userId, string $name, string $parentFolderId): string
    {
        $parentFolderId = str_replace('folder_', '', $parentFolderId);
        $parentFolderId = $parentFolderId === '' ? null : (int)$parentFolderId;

        $folder = new FileFolder();
        $folder->user_id = $userId;
        $folder->folder_id = $parentFolderId;
        $folder->foldername = $name;
        $folder->save();

        return 'folder_' . $folder->id;
    }

    public function rename(int $userId, string $id, string $newName): string
    {
        $type = str_starts_with($id, 'file_') ? 'file' : 'folder';
        $realId = str_replace(['file_', 'folder_'], '', $id);

        if ($type === 'file') {
            $file = File::where('user_id', $userId)->where('id', $realId)->first();
            if ($file) {
                $file->original_filename = $newName;
                $file->save();
                return 'file_' . $file->id;
            }
        } else {
            $folder = FileFolder::where('user_id', $userId)->where('id', $realId)->first();
            if ($folder) {
                $folder->foldername = $newName;
                $folder->save();
                return 'folder_' . $folder->id;
            }
        }

        abort(404);
    }

    public function move(int $userId, array $ids, string $destinationId): void
    {
        $destinationId = str_replace('folder_', '', $destinationId);
        $destinationId = $destinationId === '' ? null : (int)$destinationId;

        foreach ($ids as $id) {
            $type = str_starts_with($id, 'file_') ? 'file' : 'folder';
            $realId = str_replace(['file_', 'folder_'], '', $id);

            if ($type === 'file') {
                $file = File::where('user_id', $userId)->where('id', $realId)->first();
                if ($file) {
                    $file->folder_id = $destinationId;
                    $file->save();
                }
            } else {
                $folder = FileFolder::where('user_id', $userId)->where('id', $realId)->first();
                if ($folder) {
                    $folder->folder_id = $destinationId;
                    $folder->save();
                }
            }
        }
    }

    public function delete(int $userId, array $ids): void
    {
        foreach ($ids as $id) {
            $type = str_starts_with($id, 'file_') ? 'file' : 'folder';
            $realId = str_replace(['file_', 'folder_'], '', $id);

            if ($type === 'file') {
                $file = File::where('user_id', $userId)->where('id', $realId)->first();
                if ($file) {
                    if (Storage::disk(self::DISK)->exists($file->filename)) {
                        Storage::disk(self::DISK)->delete($file->filename);
                    }
                    $file->delete();
                }
            } else {
                $folder = FileFolder::where('user_id', $userId)->where('id', $realId)->first();
                if ($folder) {
                    $folder->delete_folder(); // Automatically deletes nested files and folders
                }
            }
        }
    }

    public function getFileContent(int $userId, string $id): string
    {
        $realId = str_replace('file_', '', $id);
        $file = File::where('user_id', $userId)->where('id', $realId)->firstOrFail();

        if (Storage::disk(self::DISK)->exists($file->filename)) {
            return Storage::disk(self::DISK)->get($file->filename);
        }

        abort(404, 'File not found on disk');
    }

    public function updateFileContent(int $userId, string $id, string $content): void
    {
        $realId = str_replace('file_', '', $id);
        $file = File::where('user_id', $userId)->where('id', $realId)->firstOrFail();

        Storage::disk(self::DISK)->put($file->filename, $content);
        
        $file->size = Storage::disk(self::DISK)->size($file->filename);
        $file->save();
    }

    public function download(int $userId, array $ids): BinaryFileResponse
    {
        if (count($ids) === 1) {
            $id = $ids[0];
            $type = str_starts_with($id, 'file_') ? 'file' : 'folder';
            $realId = str_replace(['file_', 'folder_'], '', $id);

            if ($type === 'file') {
                $file = File::where('user_id', $userId)->where('id', $realId)->first();
                if ($file) {
                    $fullPath = Storage::disk(self::DISK)->path($file->filename);
                    return response()->download($fullPath, $file->original_filename);
                }
            } else {
                $folder = FileFolder::where('user_id', $userId)->where('id', $realId)->first();
                if ($folder) {
                    return $this->zipFolder($folder);
                }
            }
            abort(404);
        }

        $zipFile = tempnam(sys_get_temp_dir(), 'userfiles_') . '.zip';
        $zip     = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($ids as $id) {
            $type = str_starts_with($id, 'file_') ? 'file' : 'folder';
            $realId = str_replace(['file_', 'folder_'], '', $id);

            if ($type === 'file') {
                $file = File::where('user_id', $userId)->where('id', $realId)->first();
                if ($file && Storage::disk(self::DISK)->exists($file->filename)) {
                    $zip->addFromString($file->original_filename, Storage::disk(self::DISK)->get($file->filename));
                }
            } else {
                $folder = FileFolder::where('user_id', $userId)->where('id', $realId)->first();
                if ($folder) {
                    $folder->init_zip($zip);
                }
            }
        }
        $zip->close();

        return response()->download($zipFile, 'files.zip')->deleteFileAfterSend(true);
    }

    public function buildBreadcrumbs(string $folderId): array
    {
        $folderId = str_replace('folder_', '', $folderId);
        $folderId = $folderId === '' ? null : (int)$folderId;
        $breadcrumbs = [];
        $currentId = $folderId;

        while ($currentId) {
            $folder = FileFolder::find($currentId);
            if (!$folder) break;
            
            array_unshift($breadcrumbs, [
                'name' => $folder->foldername,
                'path' => 'folder_' . $folder->id,
            ]);
            $currentId = $folder->folder_id;
        }

        array_unshift($breadcrumbs, [
            'name' => 'Root',
            'path' => '',
        ]);

        return $breadcrumbs;
    }

    private function zipFolder(FileFolder $folder): BinaryFileResponse
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'userfiles_') . '.zip';
        $zip     = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $folder->init_zip($zip);

        $zip->close();

        return response()->download($zipFile, $folder->foldername . '.zip')->deleteFileAfterSend(true);
    }
}
