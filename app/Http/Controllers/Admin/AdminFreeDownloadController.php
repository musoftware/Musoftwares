<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFreeDownloadRequest;
use App\Http\Requests\Admin\UpdateFreeDownloadRequest;
use App\Http\Resources\FreeDownloadResource;
use App\Models\FreeDownload;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminFreeDownloadController extends Controller
{
    public function index()
    {
        // Using "ordered()" scope if it exists, otherwise fallback to basic ordering
        $items = FreeDownload::query();

        if (method_exists(FreeDownload::class, 'scopeOrdered')) {
            $items = $items->ordered();
        } else {
            $items = $items->orderBy('order_column', 'asc');
        }

        $items = $items->paginate(15)
            ->through(fn ($i) => clone (new FreeDownloadResource($i))->resolve());

        return Inertia::render('Admin/FreeDownloads/Index', [
            'items' => $items,
        ]);
    }

    public function store(StoreFreeDownloadRequest $request)
    {
        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('free_downloads', 'public');
        }

        $filePath = null;
        $originalFilename = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();
            $filePath = $file->store('free_downloads/files', 'public');
        }

        FreeDownload::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'programming_language' => $validated['programming_language'] ?: null,
            'image' => $imagePath,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'is_active' => $request->boolean('is_active', true),
            'order_column' => (int) ($validated['order_column'] ?? 0),
        ]);

        return redirect()->back()->with('success', __('general.free_download_item_created_successfully'));
    }

    public function update(UpdateFreeDownloadRequest $request, FreeDownload $freeDownload)
    {
        $validated = $request->validated();

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'programming_language' => $validated['programming_language'] ?: null,
            'is_active' => $request->boolean('is_active', true),
            'order_column' => (int) ($validated['order_column'] ?? 0),
        ];

        if ($request->hasFile('image')) {
            if ($freeDownload->image) {
                Storage::disk('public')->delete($freeDownload->image);
            }
            $data['image'] = $request->file('image')->store('free_downloads', 'public');
        }

        if ($request->hasFile('file')) {
            if ($freeDownload->file_path) {
                Storage::disk('public')->delete($freeDownload->file_path);
            }
            $file = $request->file('file');
            $data['original_filename'] = $file->getClientOriginalName();
            $data['file_path'] = $file->store('free_downloads/files', 'public');
        }

        $freeDownload->update($data);

        return redirect()->back()->with('success', __('general.free_download_item_updated_successfully'));
    }

    public function destroy(FreeDownload $freeDownload)
    {
        if ($freeDownload->image) {
            Storage::disk('public')->delete($freeDownload->image);
        }
        if ($freeDownload->file_path) {
            Storage::disk('public')->delete($freeDownload->file_path);
        }
        $freeDownload->delete();

        return redirect()->back()->with('success', __('general.free_download_item_deleted_successfully'));
    }
}
