<?php

namespace App\Http\Controllers;

use App\Models\AutomationRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AutomationRuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = AutomationRule::where('user_id', $request->user()->id)->get();

        return Inertia::render('Client/Settings/Automations/Index', ['rules' => $rules]);
    }

    public function create()
    {
        return Inertia::render('Client/Settings/Automations/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'event_trigger' => 'required|string|max:255',
            'conditions' => 'nullable|array',
            'actions' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = $request->user()->id;

        AutomationRule::create($validated);

        return redirect()->route('settings.automations.index')->with('success', __('general.automation_rule_created'));
    }

    public function edit(Request $request, AutomationRule $automationRule)
    {
        $this->authorizeOwnership($request, $automationRule);

        return Inertia::render('Client/Settings/Automations/Edit', ['rule' => $automationRule]);
    }

    public function update(Request $request, AutomationRule $automationRule)
    {
        $this->authorizeOwnership($request, $automationRule);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'event_trigger' => 'required|string|max:255',
            'conditions' => 'nullable|array',
            'actions' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $automationRule->update($validated);

        return redirect()->route('settings.automations.index')->with('success', __('general.automation_rule_updated'));
    }

    public function destroy(Request $request, AutomationRule $automationRule)
    {
        $this->authorizeOwnership($request, $automationRule);

        $automationRule->delete();

        return redirect()->route('settings.automations.index')->with('success', __('general.automation_rule_deleted'));
    }

    private function authorizeOwnership(Request $request, AutomationRule $automationRule): void
    {
        if ($automationRule->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to automation rule.');
        }
    }
}
