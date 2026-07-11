<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPointPackageController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $pointPackages = PointPackage::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/PointPackages/Index', [
            'pointPackages' => $pointPackages,
            'search' => $search,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/PointPackages/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->input('is_active', true);

        PointPackage::create($validated);

        return redirect()->route('admin.point-packages.index')
            ->with('success', __('admin.point_package_created'));
    }

    public function edit(PointPackage $pointPackage)
    {
        return Inertia::render('Admin/PointPackages/Edit', [
            'pointPackage' => $pointPackage,
        ]);
    }

    public function update(Request $request, PointPackage $pointPackage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->input('is_active', true);

        $pointPackage->update($validated);

        return redirect()->route('admin.point-packages.index')
            ->with('success', __('admin.point_package_updated'));
    }

    public function destroy(PointPackage $pointPackage)
    {
        $pointPackage->delete();

        return redirect()->route('admin.point-packages.index')
            ->with('success', __('admin.point_package_deleted'));
    }
}
