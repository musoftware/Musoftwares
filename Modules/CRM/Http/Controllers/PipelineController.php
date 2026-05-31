<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Pipeline;
use Modules\CRM\Models\PipelineStage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PipelineController extends Controller
{
    public function index()
    {
        $pipelines = Pipeline::with(['stages', 'stages.leads'])->latest()->get();

        return Inertia::render('CRM/Pipelines/Index', [
            'pipelines' => $pipelines,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
        ]);

        $pipeline = Pipeline::create($validated);

        // Create default stages
        $pipeline->stages()->createMany([
            ['name' => 'New', 'order' => 1, 'type' => 'open', 'color' => '#3b82f6'],
            ['name' => 'Contacting', 'order' => 2, 'type' => 'open', 'color' => '#8b5cf6'],
            ['name' => 'Qualified', 'order' => 3, 'type' => 'open', 'color' => '#10b981'],
            ['name' => 'Won', 'order' => 4, 'type' => 'won', 'color' => '#059669', 'is_system' => true],
            ['name' => 'Lost', 'order' => 5, 'type' => 'lost', 'color' => '#ef4444', 'is_system' => true],
        ]);

        return redirect()->back()->with('success', __('crm.pipeline_created'));
    }

    public function update(Request $request, Pipeline $pipeline)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $pipeline->update($validated);
        return redirect()->back()->with('success', __('crm.pipeline_updated'));
    }

    public function destroy(Pipeline $pipeline)
    {
        $pipeline->delete();
        return redirect()->back()->with('success', __('crm.pipeline_deleted'));
    }
}
