<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with(['seller', 'category'])->where('status', 'active');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $services = $query->latest()->paginate(15);
        $categories = ServiceCategory::all();

        return Inertia::render('Marketplace/Browse', [
            'services' => $services,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function show($id)
    {
        $service = Service::with(['seller', 'category', 'packages'])->findOrFail($id);

        if ($service->status !== 'active') {
            $user = auth()->user();
            if (!$user || ($user->id !== $service->seller_id && !$user->hasRole('admin'))) {
                abort(404, 'Service not found or not active.');
            }
        }

        return Inertia::render('Marketplace/Show', [
            'service' => $service,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:marketplace_service_categories,id',
        ]);

        $validated['seller_id'] = auth()->id();
        $validated['status'] = 'draft'; // Requires approval by default

        $service = Service::create($validated);

        return redirect()->route('marketplace.services.show', $service->id)->with('success', 'Service created successfully.');
    }

    // Admin Actions
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
}
