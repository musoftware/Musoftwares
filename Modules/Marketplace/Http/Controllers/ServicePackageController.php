<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\ServicePackage;
use Modules\Core\Models\Service;

class ServicePackageController extends Controller
{
    public function store(Request $request, Service $service)
    {
        if (auth()->id() !== $service->seller_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'delivery_days' => 'required|integer|min:1',
        ]);

        $service->packages()->create($validated);

        return redirect()->back()->with('success', 'Package added.');
    }

    public function update(Request $request, Service $service, ServicePackage $package)
    {
        if (auth()->id() !== $service->seller_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'delivery_days' => 'required|integer|min:1',
        ]);

        $package->update($validated);

        return redirect()->back()->with('success', 'Package updated.');
    }

    public function destroy(Service $service, ServicePackage $package)
    {
        if (auth()->id() !== $service->seller_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized.');
        }

        $package->delete();

        return redirect()->back()->with('success', 'Package deleted.');
    }
}
