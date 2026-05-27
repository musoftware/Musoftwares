<?php

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Tools\Models\ToolSubscription;

class MarketplaceController extends Controller
{
    /**
     * Tools marketplace — browse all available tools.
     */
    public function index(Request $request): Response
    {
        $tools = collect(config('tools'))->filter(fn($t) => $t['is_active'] ?? false);

        if ($request->filled('category')) {
            $tools = $tools->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $terms = array_filter(explode(' ', strtolower($request->search)));
            $tools = $tools->filter(function ($tool) use ($terms) {
                foreach ($terms as $term) {
                    if (str_contains(strtolower($tool['title'] ?? ''), $term) ||
                        str_contains(strtolower($tool['short_description'] ?? ''), $term) ||
                        str_contains(strtolower($tool['description'] ?? ''), $term)) {
                        return true;
                    }
                }
                return false;
            });
        }

        $tools = $tools->sortByDesc('is_featured')->values();

        $page = $request->input('page', 1);
        $perPage = 12;
        
        $paginatedTools = new LengthAwarePaginator(
            $tools->forPage($page, $perPage)->values()->map(fn($t) => $this->serializeTool($t)),
            $tools->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $subscribedSlugs = [];
        $hasBrowserSubscription = false;
        if (auth()->check()) {
            $user = auth()->user();
            $subscriptionService = app(\App\Services\SubscriptionService::class);
            
            $subscribedSlugs = collect(config('tools'))
                ->filter(function ($t) use ($user, $subscriptionService) {
                    return $subscriptionService->hasAccessToTool($user, $t['slug']);
                })
                ->pluck('slug')
                ->toArray();

            $browserToolSlugs = collect(config('tools'))
                ->filter(function ($tool) {
                    $requirements = $tool['requirements'] ?? [];
                    foreach ($requirements as $req) {
                        if (str_contains(strtolower($req), 'browser extension')) {
                            return true;
                        }
                    }
                    return false;
                })
                ->pluck('slug')
                ->toArray();

            $hasBrowserSubscription = collect($subscribedSlugs)
                ->intersect($browserToolSlugs)
                ->isNotEmpty();
        }

        return Inertia::render('Tools/Explore', [
            'tools'                  => $paginatedTools,
            'categories'             => ['intelligence' => 'Intelligence', 'monitoring' => 'Monitoring', 'automation' => 'Automation', 'Media' => 'Media', 'Productivity' => 'Productivity'], // Hardcoded or from config
            'subscribedSlugs'        => $subscribedSlugs,
            'hasBrowserSubscription' => $hasBrowserSubscription,
            'filters'                => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Single tool detail page — marketing + pricing + screenshots.
     */
    public function show(string $slug): Response
    {
        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        
        if (!$tool || !($tool['is_active'] ?? false)) {
            abort(404);
        }

        $hasAccess = false;
        if (auth()->check()) {
            $hasAccess = app(\App\Services\SubscriptionService::class)->hasAccessToTool(auth()->user(), $tool['slug']);
        }

        $formattedSubscription = null;
        if ($hasAccess) {
            $plan = collect($tool['plans'] ?? [])->first();
            $formattedSubscription = [
                'id'           => 1, // Generic ID since access is managed centrally
                'plan_name'    => $plan['name'] ?? 'Platform Plan',
                'billing_cycle' => 'platform',
                'status'       => 'active',
                'expires_at'   => null,
            ];
        }

        return Inertia::render('Tools/Show', [
            'tool'             => $this->serializeToolFull($tool),
            'userSubscription' => $formattedSubscription,
        ]);
    }

    /**
     * Tool runner page — the actual web UI to run a subscribed tool.
     */
    public function run(string $slug): Response|\Illuminate\Http\RedirectResponse
    {
        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        
        if (!$tool || !($tool['is_active'] ?? false)) {
            abort(404);
        }

        $hasAccess = app(\App\Services\SubscriptionService::class)->hasAccessToTool(auth()->user(), $tool['slug']);

        if (!$hasAccess) {
            return redirect()->route('tools.show', $slug)
                ->with('error', 'You need an active Fully Genius subscription or tool pass to access this.');
        }

        $plan = collect($tool['plans'] ?? [])->first();
        $planName = $plan['name'] ?? 'Platform Plan';

        return Inertia::render('Tools/Runner', [
            'tool'         => [
                'slug'             => $tool['slug'],
                'title'            => $tool['title'],
                'icon_url'         => $tool['icon_url'] ?? null,
                'short_description' => $tool['short_description'] ?? null,
                'category'         => $tool['category'] ?? null,
                'runner_component' => $tool['runner_component'] ?? 'default',
            ],
            'subscription' => [
                'plan_name'    => $planName,
                'expires_at'   => null,
            ],
            'runtimePort'  => 18400,
            'pluginSlug'   => $tool['slug'],
        ]);
    }

    /**
     * Tool tutorial page — instructions to run/set up the tool.
     */
    public function tutorial(string $slug): Response|\Illuminate\Http\RedirectResponse
    {
        $tool = collect(config('tools'))->firstWhere('slug', $slug);
        
        if (!$tool || !($tool['is_active'] ?? false)) {
            abort(404);
        }

        $hasAccess = app(\App\Services\SubscriptionService::class)->hasAccessToTool(auth()->user(), $tool['slug']);

        if (!$hasAccess) {
            return redirect()->route('tools.show', $slug)
                ->with('error', 'You need an active Fully Genius subscription or tool pass to access this.');
        }

        $requirements = $tool['requirements'] ?? [];
        $isBrowserTool = false;
        foreach ($requirements as $req) {
            if (str_contains(strtolower($req), 'browser extension')) {
                $isBrowserTool = true;
                break;
            }
        }

        return Inertia::render('Tools/Tutorial', [
            'tool' => [
                'slug'             => $tool['slug'],
                'title'            => $tool['title'],
                'icon_url'         => $tool['icon_url'] ?? null,
                'short_description' => $tool['short_description'] ?? null,
                'is_browser_tool'  => $isBrowserTool,
            ]
        ]);
    }

    // ─── Serializers ────────────────────────────────────────────────────────────

    private function serializeTool(array $tool): array
    {
        $plans = collect($tool['plans'] ?? []);
        $lowestMonthly = $plans->min('price_monthly');

        $categories = ['intelligence' => 'Intelligence', 'monitoring' => 'Monitoring', 'automation' => 'Automation', 'Media' => 'Media', 'Productivity' => 'Productivity'];

        return [
            'id'                => $tool['guid'],
            'slug'              => $tool['slug'],
            'title'             => $tool['title'],
            'short_description' => $tool['short_description'] ?? null,
            'icon_url'          => $tool['icon_url'] ?? null,
            'category'          => $tool['category'] ?? null,
            'category_label'    => $categories[$tool['category']] ?? $tool['category'],
            'supported_os'      => $this->safeArray($tool['supported_os'] ?? []),
            'current_version'   => $tool['version'] ?? '1.0.0',
            'is_featured'       => $tool['is_featured'] ?? false,
            'starting_price'    => $lowestMonthly ?? 0,
            'is_free'           => ($lowestMonthly == 0),
            'pricing_plans'     => collect($tool['plans'] ?? [])->map(fn($p) => [
                'id'              => $p['guid'],
                'name'            => $p['name'],
                'price_monthly'   => $p['price_monthly'],
                'price_yearly'    => $p['price_yearly'],
                'features'        => $this->safeArray($p['features'] ?? []),
                'is_popular'      => $p['is_popular'] ?? false,
                'yearly_savings'  => 0,
            ])->values()->toArray(),
        ];
    }

    private function serializeToolFull(array $tool): array
    {
        $categories = ['intelligence' => 'Intelligence', 'monitoring' => 'Monitoring', 'automation' => 'Automation', 'Media' => 'Media', 'Productivity' => 'Productivity'];

        return [
            'id'                => $tool['guid'],
            'slug'              => $tool['slug'],
            'title'             => $tool['title'],
            'description'       => $tool['description'] ?? null,
            'short_description' => $tool['short_description'] ?? null,
            'icon_url'          => $tool['icon_url'] ?? null,
            'category'          => $tool['category'] ?? null,
            'category_label'    => $categories[$tool['category']] ?? $tool['category'],
            'supported_os'      => $this->safeArray($tool['supported_os'] ?? []),
            'current_version'   => $tool['version'] ?? '1.0.0',
            'is_featured'       => $tool['is_featured'] ?? false,
            'features'          => $this->safeArray($tool['features'] ?? []),
            'requirements'      => $this->safeArray($tool['requirements'] ?? []),
            'screenshots'       => [], // Removed screenshots since we dropped the model
            'pricing_plans'     => collect($tool['plans'] ?? [])->map(fn($p) => [
                'id'              => $p['guid'],
                'name'            => $p['name'],
                'price_monthly'   => $p['price_monthly'],
                'price_yearly'    => $p['price_yearly'],
                'features'        => $this->safeArray($p['features'] ?? []),
                'is_popular'      => $p['is_popular'] ?? false,
                'yearly_savings'  => 0,
            ])->values()->toArray(),
            'versions'          => [[
                'id'           => 1,
                'version'      => $tool['version'] ?? '1.0.0',
                'changelog'    => null,
                'file_size'    => '0MB',
                'is_latest'    => true,
                'is_beta'      => false,
                'released_at'  => null,
            ]], // Mocked versions since we dropped the model
        ];
    }

    private function safeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && str_starts_with(trim($value), '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($decoded)) {
                    return $decoded;
                }
                if (is_string($decoded)) {
                    $decoded2 = json_decode($decoded, true);
                    return is_array($decoded2) ? $decoded2 : [$decoded];
                }
            }
        }

        return [];
    }
}
