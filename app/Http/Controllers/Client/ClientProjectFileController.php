<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientProjectFileController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $files = $project->files()->latest()->get()->map(fn (ProjectFile $file) => [
            'id' => $file->id,
            'original_name' => $file->original_name,
            'mime' => $file->mime,
            'size' => (int) $file->size,
            'human_size' => $file->humanSize(),
            'created_at' => $file->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Client/Projects/Files', [
            'project' => ['id' => $project->id, 'name' => $project->project_name],
            'files' => fn () => $files,
        ]);
    }

    public function download(Request $request, Project $project, ProjectFile $file): StreamedResponse
    {
        $this->authorize('view', $project);
        abort_unless($file->project_id === $project->id, 404);

        $disk = config('filesystems.default');

        abort_unless(Storage::disk($disk)->exists($file->disk_path), 404);

        return Storage::disk($disk)->download($file->disk_path, $file->original_name);
    }
}
