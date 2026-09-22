<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientVaultAsset;
use App\Models\Project;
use App\Services\ClientVaultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class AdminProjectVaultController extends Controller
{
    public function __construct(
        protected ClientVaultService $vaultService
    ) {}

    public function index(Request $request, Project $project): \Inertia\Response
    {
        $this->authorize('view', $project);

        $assets = $project->vaultAssets()
            ->latest()
            ->get()
            ->map(fn (ClientVaultAsset $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'asset_type' => $a->asset_type,
                'file_mime_type' => $a->file_mime_type,
                'file_size_bytes' => $a->file_size_bytes,
                'human_size' => $a->human_size,
                'download_count' => $a->download_count,
                'last_accessed_at' => $a->last_accessed_at ? $a->last_accessed_at->diffForHumans() : null,
                'created_at' => $a->created_at ? $a->created_at->format('M d, Y H:i') : null,
            ]);

        $client = $project->user;

        return Inertia::render('Admin/Projects/Vault/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->project_name,
                'client_name' => $client?->name ?? 'Private Client',
                'client_id' => $client?->id,
            ],
            'assets' => $assets,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $client = $project->user;
        if (! $client) {
            return redirect()->back()->with('error', 'Project does not have an assigned client.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'asset_type' => ['required', 'string', 'in:source_code,delivery_build,contract,final_invoice'],
            'file' => ['required', 'file', 'max:512000'], // 500MB max
        ]);

        $this->vaultService->storeAsset($client, [
            'project_id' => $project->id,
            'title' => $validated['title'],
            'asset_type' => $validated['asset_type'],
        ], $request->file('file'));

        return redirect()->back()->with('success', 'Deliverable stored in Sovereign Client Vault.');
    }

    public function destroy(Request $request, Project $project, ClientVaultAsset $asset)
    {
        $this->authorize('update', $project);

        abort_unless($asset->project_id === $project->id, 404);

        if (Storage::disk('local')->exists($asset->storage_path)) {
            Storage::disk('local')->delete($asset->storage_path);
        }

        $asset->delete();

        return redirect()->back()->with('success', 'Vault deliverable deleted successfully.');
    }

    public function download(Request $request, Project $project, ClientVaultAsset $asset): Response
    {
        $this->authorize('view', $project);

        abort_unless($asset->project_id === $project->id, 404);

        if (! Storage::disk('local')->exists($asset->storage_path)) {
            abort(404, 'File not found in vault storage.');
        }

        return Storage::disk('local')->download($asset->storage_path, $asset->title);
    }
}
