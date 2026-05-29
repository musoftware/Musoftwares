<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;


class AdminToolController extends Controller
{
    public function index(): Response
    {
        $tools = collect(config('tools'))->values()->map(fn($t) => [
            'id'                      => $t['guid'] ?? 0,
            'slug'                    => $t['slug'],
            'title'                   => $t['title'],
            'category'                => $t['category'] ?? 'General',
            'icon_url'                => $t['icon_url'] ?? null,
            'current_version'         => $t['version'] ?? '1.0.0',
            'is_active'               => $t['is_active'] ?? false,
            'is_featured'             => $t['is_featured'] ?? false,
            'max_subscription_months' => $t['max_subscription_months'] ?? null,
            'subscriptions'           => 0,
            'downloads'               => 0,
            'deleted_at'              => null,
        ]);

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator($tools, $tools->count(), 20);

        return Inertia::render('Admin/Tools/Index', [
            'tools'      => $paginated,
            'categories' => ['intelligence' => 'Intelligence', 'monitoring' => 'Monitoring', 'automation' => 'Automation', 'Media' => 'Media', 'Productivity' => 'Productivity'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Tools/Create', [
            'categories' => ['intelligence' => 'Intelligence', 'monitoring' => 'Monitoring', 'automation' => 'Automation', 'Media' => 'Media', 'Productivity' => 'Productivity'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('admin.tools.index')->with('error', 'Tools are now managed in config/tools.php.');
    }

    public function edit(string $slug): Response
    {
        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        if (!$tool) abort(404);

        return Inertia::render('Admin/Tools/Edit', [
            'tool'       => [
                'id'                      => $tool['guid'] ?? 0,
                'slug'                    => $tool['slug'],
                'title'                   => $tool['title'],
                'description'             => $tool['description'] ?? null,
                'short_description'       => $tool['short_description'] ?? null,
                'category'                => $tool['category'] ?? 'General',
                'supported_os'            => $tool['supported_os'] ?? [],
                'icon_url'                => $tool['icon_url'] ?? null,
                'features'                => $tool['features'] ?? [],
                'requirements'            => $tool['requirements'] ?? [],
                'is_active'               => $tool['is_active'] ?? false,
                'is_featured'             => $tool['is_featured'] ?? false,
                'max_subscription_months' => $tool['max_subscription_months'] ?? null,
            ],
            'categories' => ['intelligence' => 'Intelligence', 'monitoring' => 'Monitoring', 'automation' => 'Automation', 'Media' => 'Media', 'Productivity' => 'Productivity'],
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        if (!$tool) {
            abort(404);
        }

        $request->validate([
            'max_subscription_months' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $toolGuid = $tool['guid'];
        $allTools = config('tools');

        // Update the max_subscription_months setting
        $maxMonths = $request->input('max_subscription_months');
        if ($maxMonths !== null) {
            $allTools[$toolGuid]['max_subscription_months'] = (int) $maxMonths;
        } else {
            unset($allTools[$toolGuid]['max_subscription_months']);
        }

        // Write back to config file
        $configPath = config_path('tools.php');
        $content = "<?php\r\n\r\nreturn " . var_export($allTools, true) . ";\r\n";
        file_put_contents($configPath, $content);

        return back()->with('success', __('tools.settings_updated'));
    }

    public function destroy(string $slug): RedirectResponse
    {
        return redirect()->route('admin.tools.index')->with('error', 'Tools are now managed in config/tools.php.');
    }

    // ─── Version / Release Management ───────────────────────────────────────────

    public function uploadVersion(Request $request, string $slug): RedirectResponse
    {
        return back()->with('error', 'Versions are now managed in config/tools.php and public directories.');
    }
}
