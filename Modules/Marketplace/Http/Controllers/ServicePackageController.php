<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\Service;

class ServicePackageController extends Controller
{
    public function store(Request $request, Service $service)
    {
        $this->authorize('update', $service);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'currency_id' => 'required|integer|exists:currencies,id',
            'delivery_days' => 'required|integer|min:1',
        ]);

        $service->packages()->create($validated);

        return redirect()->back()->with('success', __('general.package_added'));
    }

    public function update(Request $request, Service $service, ServicePackage $package)
    {
        $this->authorize('update', $service);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'currency_id' => 'required|integer|exists:currencies,id',
            'delivery_days' => 'required|integer|min:1',
        ]);

        $package->update($validated);

        return redirect()->back()->with('success', __('general.package_updated'));
    }

    public function destroy(Service $service, ServicePackage $package)
    {
        $this->authorize('update', $service);

        $package->delete();

        return redirect()->back()->with('success', __('general.package_deleted'));
    }
}
