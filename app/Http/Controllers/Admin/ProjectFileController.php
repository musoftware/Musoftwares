<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Project\StoreFileRequest;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProjectFileController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $files = $project->files()->latest()->get()->map(fn (ProjectFile $file) => [
            'id' => $file->id,
            'original_name' => $file->original_name,
            'mime' => $file->mime,
            'size' => (int) $file->size,
            'human_size' => $file->humanSize(),
            'created_at' => $file->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/Projects/Files/Index', [
            'project' => ['id' => $project->id, 'name' => $project->project_name],
            'files' => $files,
        ]);
    }

    public function store(StoreFileRequest $request, Project $project)
    {
        $disk = config('filesystems.default');
        $upload = $request->file('file');

        $path = $upload->store("project-files/{$project->id}", $disk);

        $project->files()->create([
            'uploaded_by' => $request->user()->id,
            'disk_path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            'mime' => $upload->getMimeType(),
            'size' => $upload->getSize(),
        ]);

        return redirect()->back()->with('success', __('general.file_uploaded'));
    }

    public function destroy(Request $request, Project $project, ProjectFile $file)
    {
        abort_unless($file->project_id === $project->id, 404);

        $disk = config('filesystems.default');
        if (Storage::disk($disk)->exists($file->disk_path)) {
            Storage::disk($disk)->delete($file->disk_path);
        }
        $file->delete();

        return redirect()->back()->with('success', __('general.file_deleted'));
    }
}
