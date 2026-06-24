<?php

namespace Modules\ERP\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Approval\WorkflowDefinition;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = tenant('id') ?? $request->user()->tenant_id;

        $workflows = WorkflowDefinition::where('tenant_id', $tenantId)
            ->with('steps')
            ->latest()
            ->get();

        return Inertia::render('ERP/Approval/Workflows/Index', [
            'workflows' => $workflows
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module_type' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $tenantId = tenant('id') ?? $request->user()->tenant_id;

        WorkflowDefinition::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'description' => $request->description,
            'module_type' => $request->module_type,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->back()->with('success', 'Workflow Definition created successfully.');
    }

    public function update(Request $request, WorkflowDefinition $workflow)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module_type' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $workflow->update($request->only('name', 'description', 'module_type', 'is_active'));

        return redirect()->back()->with('success', 'Workflow Definition updated successfully.');
    }

    public function destroy(WorkflowDefinition $workflow)
    {
        $workflow->delete();
        return redirect()->back()->with('success', 'Workflow Definition deleted successfully.');
    }
}
