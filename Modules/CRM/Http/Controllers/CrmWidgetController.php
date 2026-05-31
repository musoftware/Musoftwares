<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CRM\Models\CrmWidget;
use Inertia\Inertia;
use Illuminate\Support\Str;

class CrmWidgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $widgets = CrmWidget::withCount('leads')->latest()->paginate(15);
        
        return Inertia::render('CRM/Widgets/Index', [
            'widgets' => $widgets,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('CRM/Widgets/Form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_domains' => 'nullable|array',
            'form_config' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $validated['embed_token'] = (string) Str::uuid();
        $validated['workspace_id'] = session('workspace_id') ?? app(\Modules\CRM\Infrastructure\Context\TenantContext::class)->getWorkspaceId();

        $widget = CrmWidget::create($validated);

        return redirect()->route('crm.widgets.index')->with('success', __('crm.widget_created_successfully'));
    }

    /**
     * Display the specified resource (Show Embed Code).
     */
    public function show(CrmWidget $widget)
    {
        return Inertia::render('CRM/Widgets/Show', [
            'widget' => $widget,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CrmWidget $widget)
    {
        return Inertia::render('CRM/Widgets/Form', [
            'widget' => $widget,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CrmWidget $widget)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_domains' => 'nullable|array',
            'form_config' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $widget->update($validated);

        return redirect()->route('crm.widgets.index')->with('success', __('crm.widget_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CrmWidget $widget)
    {
        $widget->delete();

        return redirect()->route('crm.widgets.index')->with('success', __('crm.widget_deleted_successfully'));
    }
}
