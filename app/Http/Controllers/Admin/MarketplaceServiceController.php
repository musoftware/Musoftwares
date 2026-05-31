<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Inertia\Inertia;

class MarketplaceServiceController extends Controller
{
    public function allServices(Request $request)
    {
        $query = Service::with(['seller', 'category'])->withCount(['packages', 'orders']);

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

    public function approve(Service $service)
    {
        $service->update(['status' => 'active']);
        return back()->with('success', __('Service approved successfully.'));
    }

    public function reject(Service $service)
    {
        $service->update(['status' => 'rejected']);
        return back()->with('success', __('Service rejected successfully.'));
    }

    public function suspend(Service $service)
    {
        $service->update(['status' => 'suspended']);
        return back()->with('success', __('Service suspended successfully.'));
    }

    public function feature(Service $service)
    {
        $service->update(['is_featured' => !$service->is_featured]);
        return back()->with('success', __('Service featured status updated.'));
    }

    public function edit(Service $service)
    {
        $categories = ServiceCategory::orderBy('name')->get(['id', 'name', 'slug']);
        $service->load('packages');

        return Inertia::render('Admin/Marketplace/Edit', [
            'categories' => $categories,
            'service' => $service,
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:marketplace_service_categories,id',
            'status' => 'required|in:active,draft,suspended,rejected',
            'packages' => 'required|array|min:1|max:3',
            'packages.*.name' => 'required|string|max:255',
            'packages.*.description' => 'required|string',
            'packages.*.price' => 'required|numeric|min:1',
            'packages.*.delivery_days' => 'required|integer|min:1',
        ]);

        $service->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'status' => $validated['status'],
        ]);

        $service->packages()->delete();
        $service->packages()->createMany($validated['packages']);

        return redirect()->route('admin.marketplace.services.all')->with('success', __('Service updated successfully.'));
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return back()->with('success', __('Service deleted successfully.'));
    }
}
