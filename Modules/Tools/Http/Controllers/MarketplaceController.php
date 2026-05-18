<?php

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tools\Models\Tool;
use Modules\Tools\Models\ToolLicense;
use Modules\Tools\Models\ToolSubscription;

class MarketplaceController extends Controller
{
    /**
     * Tools marketplace — browse all available tools.
     */
    public function index(Request $request): Response
    {
        $query = Tool::query()
            ->where('is_active', true)
            ->with(['pricingPlans', 'latestVersion'])
            ->withCount('downloads');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('short_description', 'like', "%{$request->search}%");
            });
        }

        $tools = $query->orderByDesc('is_featured')
            ->orderByDesc('download_count')
            ->paginate(12)
            ->through(fn($tool) => $this->serializeTool($tool, auth()->id()));

        // Get slugs the user is subscribed to for badge overlay
        $subscribedSlugs = [];
        if (auth()->check()) {
            $subscribedSlugs = ToolSubscription::where('user_id', auth()->id())
                ->where('status', 'active')
                ->with('tool:id,slug')
                ->get()
                ->pluck('tool.slug')
                ->toArray();
        }

        return Inertia::render('Tools/Explore', [
            'tools'          => $tools,
            'categories'     => Tool::$categories,
            'subscribedSlugs' => $subscribedSlugs,
            'filters'        => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Single tool detail page — marketing + pricing + screenshots.
     */
    public function show(string $slug): Response
    {
        $tool = Tool::where('slug', $slug)
            ->where('is_active', true)
            ->with(['pricingPlans', 'screenshots', 'versions' => fn($q) => $q->orderByDesc('released_at')->limit(10)])
            ->firstOrFail();

        $userLicense = null;
        $userSubscription = null;

        if (auth()->check()) {
            $userSubscription = ToolSubscription::where('user_id', auth()->id())
                ->where('tool_id', $tool->id)
                ->where('status', 'active')
                ->with('plan')
                ->latest()
                ->first();

            $userLicense = ToolLicense::where('user_id', auth()->id())
                ->where('tool_id', $tool->id)
                ->where('status', 'active')
                ->with('activeDevices')
                ->latest()
                ->first();
        }

        return Inertia::render('Tools/Show', [
            'tool'             => $this->serializeToolFull($tool),
            'userSubscription' => $userSubscription ? [
                'id'           => $userSubscription->id,
                'plan_name'    => $userSubscription->plan->name ?? 'N/A',
                'billing_cycle' => $userSubscription->billing_cycle,
                'status'       => $userSubscription->status,
                'expires_at'   => $userSubscription->expires_at?->toDateString(),
            ] : null,
            'userLicense'      => $userLicense ? [
                'id'             => $userLicense->id,
                'license_key'    => $userLicense->license_key,
                'max_devices'    => $userLicense->max_devices,
                'active_devices' => $userLicense->activeDevices->count(),
            ] : null,
        ]);
    }

    // ─── Serializers ────────────────────────────────────────────────────────────

    private function serializeTool(Tool $tool, ?int $userId): array
    {
        $lowestMonthly = $tool->pricingPlans->min('price_monthly');

        return [
            'id'                => $tool->id,
            'slug'              => $tool->slug,
            'title'             => $tool->title,
            'short_description' => $tool->short_description,
            'icon_url'          => $tool->icon_url,
            'category'          => $tool->category,
            'category_label'    => Tool::$categories[$tool->category] ?? $tool->category,
            'supported_os'      => $this->safeArray($tool->supported_os),
            'current_version'   => $tool->current_version,
            'download_count'    => $tool->downloads_count,
            'is_featured'       => $tool->is_featured,
            'starting_price'    => $lowestMonthly ?? 0,
            'is_free'           => $lowestMonthly == 0,
        ];
    }

    private function serializeToolFull(Tool $tool): array
    {
        return [
            'id'                => $tool->id,
            'slug'              => $tool->slug,
            'title'             => $tool->title,
            'description'       => $tool->description,
            'short_description' => $tool->short_description,
            'icon_url'          => $tool->icon_url,
            'category'          => $tool->category,
            'category_label'    => Tool::$categories[$tool->category] ?? $tool->category,
            'supported_os'      => $this->safeArray($tool->supported_os),
            'current_version'   => $tool->current_version,
            'download_count'    => $tool->download_count,
            'is_featured'       => $tool->is_featured,
            'features'          => $this->safeArray($tool->features),
            'requirements'      => $this->safeArray($tool->requirements),
            'screenshots'       => $tool->screenshots->map(fn($s) => [
                'id'      => $s->id,
                'url'     => $s->url,
                'caption' => $s->caption,
            ])->values()->toArray(),
            'pricing_plans'     => $tool->pricingPlans->map(fn($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'price_monthly'   => $p->price_monthly,
                'price_yearly'    => $p->price_yearly,
                'max_devices'     => $p->max_devices,
                'features'        => $this->safeArray($p->features),
                'is_popular'      => $p->is_popular,
                'yearly_savings'  => $p->yearly_savings,
            ])->values()->toArray(),
            'versions'          => $tool->versions->map(fn($v) => [
                'id'           => $v->id,
                'version'      => $v->version,
                'changelog'    => $v->changelog,
                'file_size'    => $v->formatted_size,
                'is_latest'    => $v->is_latest,
                'is_beta'      => $v->is_beta,
                'released_at'  => $v->released_at?->toDateString(),
            ])->values()->toArray(),
        ];
    }

    /**
     * Safely convert a value to a plain PHP array.
     * Handles: null, plain array, JSON string, and double-encoded JSON string.
     */
    private function safeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && str_starts_with(trim($value), '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Handle double-encoded: decoded value might still be a JSON string
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
