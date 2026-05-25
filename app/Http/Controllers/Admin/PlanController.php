<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModulePlan;
use App\Services\PlanService;
use App\Http\Requests\Admin\Plan\StorePlanRequest;
use App\Http\Requests\Admin\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function __construct(
        protected PlanService $planService
    ) {}
    public function index()
    {
        $plans = ModulePlan::orderBy('module')->orderBy('price')->get();
        return Inertia::render('Admin/Plans/Index', [
            'plans' => PlanResource::collection($plans)->resolve(),
        ]);
    }

    public function store(StorePlanRequest $request)
    {
        $this->planService->createPlan($request->validated());

        return redirect()->back()->with('success', 'Plan created successfully.');
    }

    public function update(UpdatePlanRequest $request, $id)
    {
        $this->planService->updatePlan($id, $request->validated());

        return redirect()->back()->with('success', 'Plan updated successfully.');
    }

    public function destroy($id)
    {
        $this->planService->deletePlan($id);

        return redirect()->back()->with('success', 'Plan deleted.');
    }
}
