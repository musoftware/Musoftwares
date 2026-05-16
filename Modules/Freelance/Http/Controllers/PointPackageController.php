<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\PointPackage;
use Illuminate\Http\Request;

class PointPackageController extends Controller
{
    public function index()
    {
        $packages = PointPackage::orderBy('points')->get();
        return response()->json($packages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
        ]);

        PointPackage::create($validated);
        return back()->with('success', 'Point package created.');
    }

    public function update(Request $request, PointPackage $pointPackage)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
        ]);

        $pointPackage->update($validated);
        return back()->with('success', 'Point package updated.');
    }

    public function destroy(PointPackage $pointPackage)
    {
        $pointPackage->delete();
        return back()->with('success', 'Point package deleted.');
    }
}
