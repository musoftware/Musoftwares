<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\ModulePlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        $plans = ModulePlan::orderBy('module')->orderBy('price')->get();
        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing' => 'required|string|max:50',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $plan = new ModulePlan();
        $plan->module = $validated['module'];
        $plan->name = $validated['name'];
        $plan->price = $validated['price'];
        $plan->billing = $validated['billing'];
        $plan->features = $validated['features'] ?? [];
        $plan->is_active = $validated['is_active'] ?? true;
        $plan->save();

        return redirect()->back()->with('success', 'Plan created successfully.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'module' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing' => 'required|string|max:50',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $plan = ModulePlan::findOrFail($id);
        $plan->module = $validated['module'];
        $plan->name = $validated['name'];
        $plan->price = $validated['price'];
        $plan->billing = $validated['billing'];
        $plan->features = $validated['features'] ?? [];
        $plan->is_active = $validated['is_active'] ?? true;
        $plan->save();

        return redirect()->back()->with('success', 'Plan updated successfully.');
    }

    public function destroy($id)
    {
        $plan = ModulePlan::findOrFail($id);
        $plan->delete();

        return redirect()->back()->with('success', 'Plan deleted.');
    }
}
