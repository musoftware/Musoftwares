<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;

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
