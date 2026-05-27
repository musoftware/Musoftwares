<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Illuminate\Support\Facades\DB;

class MarketplaceServiceController extends Controller
{
    /**
     * Display all services for admin moderation.
     */
    public function index(Request $request)
    {
        $query = Service::with(['seller', 'category', 'packages'])->withCount('orders');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('seller', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['created_at', 'title', 'status', 'orders_count'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $services = $query->paginate(15)->withQueryString();
        $categories = ServiceCategory::orderBy('name')->get(['id', 'name']);

        // Summary stats
        $stats = [
            'total'     => Service::count(),
            'active'    => Service::where('status', 'active')->count(),
            'pending'   => Service::where('status', 'draft')->count(),
            'suspended' => Service::where('status', 'suspended')->count(),
            'rejected'  => Service::where('status', 'rejected')->count(),
            'featured'  => Service::where('is_featured', true)->count(),
        ];

        return Inertia::render('Admin/Marketplace/All', [
            'services'   => $services,
            'categories' => $categories,
            'filters'    => $request->only(['search', 'status', 'category_id', 'sort_by', 'sort_dir']),
            'stats'      => $stats,
        ]);
    }

    /**
     * Display pending (draft) services that need approval.
     */
    public function pending(Request $request)
    {
        $query = Service::with(['seller', 'category', 'packages'])->where('status', 'draft');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('seller', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $services = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Marketplace/Pending', [
            'services' => $services,
            'filters' => $request->only(['search']),
        ]);
    }

    // ── Admin Actions ────────────────────────────────────────────────────────

    public function edit($id)
    {
        $service = Service::with(['packages'])->findOrFail($id);
        $categories = ServiceCategory::orderBy('name')->get(['id', 'name']);
        
        return Inertia::render('Admin/Marketplace/Edit', [
            'service' => $service,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'title'                    => 'required|string|max:255',
            'description'              => 'required|string|min:100',
            'category_id'              => 'required|exists:marketplace_service_categories,id',
            'status'                   => 'required|in:active,draft,suspended,rejected',
            'tags'                     => 'nullable|array|max:5',
            'tags.*'                   => 'string|max:40',
            'video_url'                => 'nullable|url|max:255',
            'packages'                 => 'required|array|min:1|max:3',
            'packages.*.id'            => 'nullable|exists:marketplace_service_packages,id',
            'packages.*.name'          => 'required|string|max:80',
            'packages.*.description'   => 'required|string|max:500',
            'packages.*.price'         => 'required|numeric|min:1',
            'packages.*.currency_code' => 'required|string|size:3',
            'packages.*.delivery_days' => 'required|integer|min:1|max:365',
            'packages.*.revisions'     => 'nullable|integer|min:-1',
            'packages.*.features'      => 'nullable|array',
            'packages.*.features.*'    => 'string|max:60',
            'faq'                      => 'nullable|array|max:10',
            'faq.*.question'           => 'required|string|max:200',
            'faq.*.answer'             => 'required|string|max:1000',
            'requirements'             => 'nullable|array|max:10',
            'requirements.*'           => 'string|max:300',
        ]);

        DB::transaction(function () use ($service, $validated) {
            $service->update([
                'title'        => $validated['title'],
                'description'  => $validated['description'],
                'category_id'  => $validated['category_id'],
                'status'       => $validated['status'],
                'tags'         => $validated['tags'] ?? [],
                'faq'          => $validated['faq'] ?? [],
                'requirements' => $validated['requirements'] ?? [],
                'video_url'    => $validated['video_url'] ?? null,
            ]);

            $existingPackageIds = $service->packages()->pluck('id')->toArray();
            $updatedPackageIds = [];

            foreach ($validated['packages'] as $pkg) {
                if (isset($pkg['id']) && in_array($pkg['id'], $existingPackageIds)) {
                    // Update existing
                    ServicePackage::where('id', $pkg['id'])->update([
                        'name'          => $pkg['name'],
                        'description'   => $pkg['description'],
                        'price'         => $pkg['price'],
                        'currency_code' => $pkg['currency_code'],
                        'delivery_days' => $pkg['delivery_days'],
                        'revisions'     => $pkg['revisions'] ?? 2,
                        'features'      => $pkg['features'] ?? [],
                    ]);
                    $updatedPackageIds[] = $pkg['id'];
                } else {
                    // Create new
                    $newPkg = ServicePackage::create([
                        'service_id'    => $service->id,
                        'name'          => $pkg['name'],
                        'description'   => $pkg['description'],
                        'price'         => $pkg['price'],
                        'currency_code' => $pkg['currency_code'],
                        'delivery_days' => $pkg['delivery_days'],
                        'revisions'     => $pkg['revisions'] ?? 2,
                        'features'      => $pkg['features'] ?? [],
                    ]);
                    $updatedPackageIds[] = $newPkg->id;
                }
            }

            // Delete packages that were removed
            $packagesToDelete = array_diff($existingPackageIds, $updatedPackageIds);
            if (!empty($packagesToDelete)) {
                ServicePackage::whereIn('id', $packagesToDelete)->delete();
            }
        });

        return redirect()->route('admin.marketplace.services.all')->with('success', 'Service updated successfully.');
    }

    public function approve($id)
    {
        $service = Service::findOrFail($id);
        $service->update(['status' => 'active']);

        return redirect()->back()->with('success', 'Service approved.');
    }

    public function reject($id)
    {
        $service = Service::findOrFail($id);
        $service->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Service rejected.');
    }

    public function feature($id)
    {
        $service = Service::findOrFail($id);
        $service->update(['is_featured' => !$service->is_featured]);

        return redirect()->back()->with('success', 'Service feature status toggled.');
    }

    public function suspend($id)
    {
        $service = Service::findOrFail($id);
        $service->update(['status' => 'suspended']);

        return redirect()->back()->with('success', 'Service suspended.');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return redirect()->back()->with('success', 'Service deleted successfully.');
    }
}
