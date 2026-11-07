<?php

namespace Modules\Marketplace\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Modules\Marketplace\Http\Requests\AdminServiceRequest;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Services\AdminServiceWriter;
use Modules\Marketplace\Services\ServiceModeration;
use Inertia\Inertia;

class MarketplaceServiceController extends Controller
{
    public function allServices(Request $request)
    {
        $query = Service::with(['seller', 'category', 'packages'])->withCount(['packages', 'orders']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('seller', function ($sellerQuery) use ($search) {
                      $sellerQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('category_id') && $request->input('category_id') !== 'all') {
            $query->where('category_id', $request->input('category_id'));
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // Handle custom sorting logic if needed, else default to column
        if (in_array($sortBy, ['title', 'status', 'created_at', 'orders_count'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest();
        }

        $services = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Service::count(),
            'active' => Service::where('status', 'active')->count(),
            'pending' => Service::where('status', 'draft')->count(), // 'draft' is used as pending based on ServiceController
            'suspended' => Service::where('status', 'suspended')->count(),
            'rejected' => Service::where('status', 'rejected')->count(),
            'featured' => Service::where('is_featured', true)->count(),
        ];

        return Inertia::render('Admin/Marketplace/All', [
            'services' => $services,
            'categories' => ServiceCategory::all(),
            'filters' => $request->only(['search', 'status', 'category_id', 'sort_by', 'sort_dir']),
            'stats' => $stats,
        ]);
    }

    public function pendingServices(Request $request)
    {
        // 'draft' status usually means pending approval in this system
        $query = Service::with(['seller', 'category'])->withCount(['packages', 'orders'])
                        ->where('status', 'draft');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('seller', function ($sellerQuery) use ($search) {
                      $sellerQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('category_id') && $request->input('category_id') !== 'all') {
            $query->where('category_id', $request->input('category_id'));
        }

        $services = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Marketplace/Pending', [
            'services' => $services,
            'categories' => ServiceCategory::all(),
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function approve(Request $request, Service $service, ServiceModeration $moderation)
    {
        $moderation->approve($service, $request->user());

        return back()->with('success', __('general.service_approved_successfully'));
    }

    public function reject(Request $request, Service $service, ServiceModeration $moderation)
    {
        $validated = $request->validate(['note' => 'nullable|string|max:1000']);
        $moderation->reject($service, $validated['note'] ?? null);

        return back()->with('success', __('general.service_rejected_successfully'));
    }

    public function bulkModerate(Request $request, ServiceModeration $moderation)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer',
            'action' => 'required|in:approve,reject',
            'note' => 'nullable|required_if:action,reject|string|max:1000',
        ]);

        $services = Service::whereIn('id', $validated['ids'])->get();
        foreach ($services as $service) {
            $validated['action'] === 'approve'
                ? $moderation->approve($service, $request->user())
                : $moderation->reject($service, $validated['note']);
        }

        return back()->with('success', __('admin.marketplace_bulk_done', ['count' => $services->count()]));
    }

    public function suspend(Service $service)
    {
        $service->update(['status' => 'suspended']);
        return back()->with('success', __('general.service_suspended_successfully'));
    }

    public function feature(Service $service)
    {
        $service->update(['is_featured' => !$service->is_featured]);
        return back()->with('success', __('general.service_featured_status_updated'));
    }

    public function create()
    {
        return Inertia::render('Admin/Marketplace/Edit', $this->formProps(null));
    }

    public function store(AdminServiceRequest $request, AdminServiceWriter $writer)
    {
        $writer->create($request->validated(), $request->file('gallery', []), $request->user());

        return redirect()->route('admin.marketplace.services.all')->with('success', __('admin.marketplace_service_created'));
    }

    public function edit(Service $service)
    {
        return Inertia::render('Admin/Marketplace/Edit', $this->formProps($service->load(['packages', 'extras'])));
    }

    public function update(AdminServiceRequest $request, Service $service, AdminServiceWriter $writer)
    {
        $writer->update($service, $request->validated(), $request->file('gallery', []), $request->user());

        return redirect()->route('admin.marketplace.services.all')->with('success', __('general.service_updated_successfully'));
    }

    private function formProps(?Service $service): array
    {
        return [
            'categories' => ServiceCategory::orderBy('name')->get(['id', 'name', 'slug']),
            'currencies' => Currency::orderBy('id')->get(['id', 'currency', 'symbol']),
            'service' => $service,
        ];
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return back()->with('success', __('general.service_deleted_successfully'));
    }
}
