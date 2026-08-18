<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Marketplace\Models\ServiceCategory;

class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of service categories for Admin dashboard.
     */
    public function index(): Response
    {
        $categories = ServiceCategory::withCount('services')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Marketplace/Categories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created service category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('marketplace_service_categories', 'name')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $validated['slug'] = $this->generateUniqueSlug($validated['name']);
                ServiceCategory::create($validated);
            });

            return redirect()->back()->with('success', __('general.category_created'));
        } catch (\Exception $e) {
            Log::error('Failed to create service category: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors([
                'name' => __('Failed to create category. Please try again.'),
            ]);
        }
    }

    /**
     * Update the specified service category in storage.
     */
    public function update(Request $request, ServiceCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('marketplace_service_categories', 'name')
                    ->ignore($category->id)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            DB::transaction(function () use ($category, $validated) {
                // If name changed, regenerate slug dynamically & collision-free
                if ($category->name !== $validated['name']) {
                    $validated['slug'] = $this->generateUniqueSlug($validated['name'], $category->id);
                }

                $category->update($validated);
            });

            return redirect()->back()->with('success', __('general.category_updated'));
        } catch (\Exception $e) {
            Log::error("Failed to update service category ID {$category->id}: ".$e->getMessage(), [
                'category_id' => $category->id,
                'request' => $request->all(),
            ]);

            return redirect()->back()->withErrors([
                'name' => __('Failed to update category. Please try again.'),
            ]);
        }
    }

    /**
     * Return active categories list for API consumption.
     */
    public function apiIndex(): JsonResponse
    {
        $categories = ServiceCategory::withCount('services')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Remove the specified service category from storage.
     */
    public function destroy(ServiceCategory $category): RedirectResponse
    {
        // Guard against deleting categories that are currently linked to active services
        if ($category->services()->exists()) {
            return redirect()->back()->withErrors([
                'category' => __('general.cannot_delete_category_with_services'),
            ])->with('error', __('general.cannot_delete_category_with_services'));
        }

        try {
            DB::transaction(function () use ($category) {
                $category->delete();
            });

            return redirect()->back()->with('success', __('general.category_deleted'));
        } catch (\Exception $e) {
            Log::error("Failed to delete service category ID {$category->id}: ".$e->getMessage(), [
                'category_id' => $category->id,
            ]);

            return redirect()->back()->withErrors([
                'category' => __('Failed to delete category. Please try again.'),
            ]);
        }
    }

    /**
     * Display public page for a specific category.
     */
    public function showCategory(string $slug, Request $request)
    {
        $category = ServiceCategory::where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? (int)$slug : 0)
            ->firstOrFail();

        $services = \Modules\Marketplace\Models\Service::with(['seller', 'category', 'packages.currency'])
            ->where('category_id', $category->id)
            ->where('status', 'active')
            ->latest()
            ->paginate(15);

        $categories = \Illuminate\Support\Facades\Cache::remember('mk_categories_list', 3600, function () {
            return ServiceCategory::orderBy('name')->get();
        });

        $services->getCollection()->transform(function ($service) {
            $service->makeHidden(['description', 'description_translations', 'auto_reply', 'auto_reply_translations', 'faq', 'requirements']);
            return $service;
        });

        $schemaJson = \Modules\Marketplace\Helpers\MarketplaceSchemaHelper::forCategory($category, $services->items());

        $canonicalUrl = route('marketplace.categories.show', ['slug' => $category->slug]);

        $filters = [
            'category' => $category->slug,
            'category_id' => $category->id,
            'category_name' => $category->name,
        ];

        $meta = [
            'title' => "{$category->name} Services | MuSoftwares Marketplace",
            'description' => $category->description ?? "Explore {$category->name} services and digital solutions on MuSoftwares Marketplace.",
            'url' => $canonicalUrl,
            'canonical_url' => $canonicalUrl,
            'en_url' => $canonicalUrl.'?lang=en',
            'ar_url' => $canonicalUrl.'?lang=ar',
            'type' => 'website',
            'schema_json' => $schemaJson,
        ];

        $viewerCurrency = \App\Helpers\FinanceHelper::instance()->getViewerCurrency($request);

        return view('marketplace::public.index', compact('services', 'categories', 'filters', 'meta', 'viewerCurrency'));
    }

    /**
     * Generate a unique, collision-free URL slug.
     */
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        if (empty($baseSlug)) {
            $baseSlug = 'category';
        }

        $slug = $baseSlug;
        $count = 1;

        while (ServiceCategory::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
}



