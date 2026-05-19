<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tools\Models\Tool;
use Modules\Tools\Models\ToolVersion;

class AdminToolController extends Controller
{
    public function index(): Response
    {
        $tools = Tool::withTrashed()
            ->withCount(['subscriptions', 'licenses', 'downloads'])
            ->with('latestVersion')
            ->latest()
            ->paginate(20)
            ->through(fn($t) => [
                'id'              => $t->id,
                'slug'            => $t->slug,
                'title'           => $t->title,
                'category'        => $t->category,
                'icon_url'        => $t->icon_url,
                'current_version' => $t->current_version,
                'is_active'       => $t->is_active,
                'is_featured'     => $t->is_featured,
                'subscriptions'   => $t->subscriptions_count,
                'downloads'       => $t->downloads_count,
                'deleted_at'      => $t->deleted_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Tools/Index', [
            'tools'      => $tools,
            'categories' => Tool::$categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Tools/Create', [
            'categories' => Tool::$categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'             => ['required', 'string', 'max:120'],
            'slug'              => ['required', 'string', 'max:80', 'unique:tools,slug', 'regex:/^[a-z0-9-]+$/'],
            'description'       => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:250'],
            'category'          => ['required', 'in:' . implode(',', array_keys(Tool::$categories))],
            'supported_os'      => ['required', 'array'],
            'icon'              => ['nullable', 'image', 'max:2048'],
            'features'          => ['nullable', 'array'],
            'requirements'      => ['nullable', 'array'],
            'is_active'         => ['boolean'],
            'is_featured'       => ['boolean'],
        ]);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('tools/icons', 'public');
        }

        Tool::create($data);

        return redirect()->route('admin.tools.index')->with('success', 'Tool created successfully.');
    }

    public function edit(Tool $tool): Response
    {
        return Inertia::render('Admin/Tools/Edit', [
            'tool'       => [
                'id'                => $tool->id,
                'slug'              => $tool->slug,
                'title'             => $tool->title,
                'description'       => $tool->description,
                'short_description' => $tool->short_description,
                'category'          => $tool->category,
                'supported_os'      => $tool->supported_os,
                'icon_url'          => $tool->icon_url,
                'features'          => $tool->features ?? [],
                'requirements'      => $tool->requirements ?? [],
                'is_active'         => $tool->is_active,
                'is_featured'       => $tool->is_featured,
            ],
            'categories' => Tool::$categories,
        ]);
    }

    public function update(Request $request, Tool $tool): RedirectResponse
    {
        $data = $request->validate([
            'title'             => ['required', 'string', 'max:120'],
            'description'       => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:250'],
            'category'          => ['required', 'in:' . implode(',', array_keys(Tool::$categories))],
            'supported_os'      => ['required', 'array'],
            'icon'              => ['nullable', 'image', 'max:2048'],
            'features'          => ['nullable', 'array'],
            'requirements'      => ['nullable', 'array'],
            'is_active'         => ['boolean'],
            'is_featured'       => ['boolean'],
        ]);

        if ($request->hasFile('icon')) {
            if ($tool->icon) Storage::disk('public')->delete($tool->icon);
            $data['icon'] = $request->file('icon')->store('tools/icons', 'public');
        }

        $tool->update($data);

        return back()->with('success', 'Tool updated.');
    }

    public function destroy(Tool $tool): RedirectResponse
    {
        $tool->delete();
        return redirect()->route('admin.tools.index')->with('success', 'Tool archived.');
    }

    // ─── Version / Release Management ───────────────────────────────────────────

    public function uploadVersion(Request $request, Tool $tool): RedirectResponse
    {
        $data = $request->validate([
            'version'    => ['required', 'string', 'regex:/^\d+\.\d+\.\d+$/'],
            'changelog'  => ['nullable', 'string'],
            'file'       => ['required', 'file', 'mimes:zip,exe', 'max:512000'], // 500MB
            'is_beta'    => ['boolean'],
            'set_latest' => ['boolean'],
        ]);

        $path     = $request->file('file')->store('tools/releases/' . $tool->slug, 'local');
        $checksum = hash_file('sha256', $request->file('file')->getRealPath());

        $version = ToolVersion::create([
            'tool_id'      => $tool->id,
            'version'      => $data['version'],
            'changelog'    => $data['changelog'] ?? null,
            'file_path'    => $path,
            'file_name'    => Str::slug($tool->title) . '-v' . $data['version'] . '.zip',
            'file_size'    => $request->file('file')->getSize(),
            'checksum'     => $checksum,
            'is_beta'      => $data['is_beta'] ?? false,
            'released_at'  => now(),
        ]);

        if ($request->boolean('set_latest')) {
            $version->markAsLatest();
        }

        return back()->with('success', "Version {$data['version']} uploaded successfully.");
    }
}
