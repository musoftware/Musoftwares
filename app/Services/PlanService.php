<?php

namespace App\Services;

use App\Models\ModulePlan;

class PlanService extends BaseService
{

    public function createPlan(array $data): ModulePlan
    {
        $plan = new ModulePlan();
        $plan->module    = $data['module'];
        $plan->name      = $data['name'];
        $plan->price     = $data['price'];
        $plan->billing   = $data['billing'];
        $plan->features  = $data['features'] ?? [];
        $plan->is_active = $data['is_active'] ?? true;
        $plan->save();

        return $plan;
    }

    public function updatePlan(int $id, array $data): ModulePlan
    {
        $plan = ModulePlan::findOrFail($id);
        $plan->module    = $data['module'];
        $plan->name      = $data['name'];
        $plan->price     = $data['price'];
        $plan->billing   = $data['billing'];
        $plan->features  = $data['features'] ?? [];
        $plan->is_active = $data['is_active'] ?? true;
        $plan->save();

        return $plan;
    }

    public function deletePlan(int $id): void
    {
        $plan = ModulePlan::findOrFail($id);
        $plan->delete();
    }
}
