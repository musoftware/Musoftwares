<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserFileService;
use App\Http\Requests\Admin\User\UploadUserFileRequest;
use App\Http\Requests\Admin\User\NewUserFolderRequest;
use App\Http\Requests\Admin\User\RenameUserFileRequest;
use App\Http\Requests\Admin\User\MoveUserFileRequest;
use App\Http\Requests\Admin\User\DeleteUserFileRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
    public function __construct(
        protected UserFileService $userFileService
    ) {}

    /**
     * List files and folders at the given path (breadcrumb navigation).
     * Recovered from old project: FileController::load()
     */
    public function index(Request $request, int $userId)
    {
        $user   = User::findOrFail($userId);
        $folder = $request->get('folder', '');

        $data = $this->userFileService->getFilesAndFolders($userId, $folder);
        $breadcrumbs = $this->userFileService->buildBreadcrumbs($folder);

        return Inertia::render('Admin/Users/Files', [
            'user'           => ['id' => $user->id, 'name' => $user->name],
            'files'          => $data['files'],
            'folders'        => $data['folders'],
            'breadcrumbs'    => $breadcrumbs,
            'current_folder' => $folder,
        ]);
    }

    /**
     * Upload a file for a user.
     * Recovered from old project: FileController::upload_direct()
     */
    public function upload(UploadUserFileRequest $request, int $userId)
    {
        User::findOrFail($userId);
        $folder = (string) $request->get('folder', '');

        $result = $this->userFileService->uploadFile($userId, $request->file('file'), $folder);

        return response()->json(array_merge(['success' => true], $result));
    }

    /**
     * Create a new folder.
     * Recovered from old project: FileController::new_folder()
     */
    public function newFolder(NewUserFolderRequest $request, int $userId)
    {
        User::findOrFail($userId);

        $path = $this->userFileService->createFolder($userId, $request->input('name'), (string) $request->get('parent', ''));

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

        return $this->userFileService->download($userId, $paths);
    }

    /**
     * Rename a file or folder.
     * Recovered from old project: FileController::rename()
     */
    public function rename(RenameUserFileRequest $request, int $userId)
    {
        User::findOrFail($userId);

        $newPath = $this->userFileService->rename($userId, $request->input('path'), $request->input('new_name'));

        return response()->json(['success' => true, 'new_path' => $newPath]);
    }

    /**
     * Move files/folders to a new location.
     * Recovered from old project: FileController::move()
     */
    public function move(MoveUserFileRequest $request, int $userId)
    {
        User::findOrFail($userId);

        $this->userFileService->move($userId, $request->input('paths'), $request->input('destination'));

        return response()->json(['success' => true]);
    }

    /**
     * Delete files or folders.
     * Recovered from old project: FileController::delete()
     */
    public function delete(DeleteUserFileRequest $request, int $userId)
    {
        User::findOrFail($userId);

        $this->userFileService->delete($userId, $request->input('paths'));

        return response()->json(['success' => true]);
    }
}
