<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Widget;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WidgetController extends Controller
{
    public function index()
    {
        $widgets = Widget::latest()->paginate(20);

        return Inertia::render('CRM/Widgets/Index', [
            'widgets' => $widgets,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:embed,popup,floating',
            'pipeline_id' => 'nullable|exists:pipelines,id',
            'pipeline_stage_id' => 'nullable|exists:pipeline_stages,id',
            'is_active' => 'boolean',
        ]);

        Widget::create($validated);

        return redirect()->back()->with('success', __('crm.widget_created'));
    }

    public function update(Request $request, Widget $widget)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:embed,popup,floating',
            'pipeline_id' => 'nullable|exists:pipelines,id',
            'pipeline_stage_id' => 'nullable|exists:pipeline_stages,id',
            'is_active' => 'boolean',
        ]);

        $widget->update($validated);

        return redirect()->back()->with('success', __('crm.widget_updated'));
    }

    public function destroy(Widget $widget)
    {
        $widget->delete();
        return redirect()->back()->with('success', __('crm.widget_deleted'));
    }
}
