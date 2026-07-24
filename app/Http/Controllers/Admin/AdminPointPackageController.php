<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PointPackage\StorePointPackageRequest;
use App\Http\Requests\Admin\PointPackage\UpdatePointPackageRequest;
use App\Models\PointPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPointPackageController extends Controller
{
    public function index(Request $request): Response
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

    public function create(): Response
    {
        return Inertia::render('Admin/PointPackages/Create');
    }

    public function store(StorePointPackageRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->input('is_active', true);

        PointPackage::create($validated);

        return redirect()->route('admin.point-packages.index')
            ->with('success', __('admin.point_package_created'));
    }

    public function edit(PointPackage $pointPackage): Response
    {
        return Inertia::render('Admin/PointPackages/Edit', [
            'pointPackage' => $pointPackage,
        ]);
    }

    public function update(UpdatePointPackageRequest $request, PointPackage $pointPackage): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->input('is_active', true);

        $pointPackage->update($validated);

        return redirect()->route('admin.point-packages.index')
            ->with('success', __('admin.point_package_updated'));
    }

    public function destroy(PointPackage $pointPackage): RedirectResponse
    {
        $pointPackage->delete();

        return redirect()->route('admin.point-packages.index')
            ->with('success', __('admin.point_package_deleted'));
    }
}
