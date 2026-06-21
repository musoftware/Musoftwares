<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AutomationRuleController extends Controller
{
    public function index()
    {
        $rules = \App\Models\AutomationRule::where('user_id', auth()->id())->get();
        return \Inertia\Inertia::render('Client/Settings/Automations/Index', ['rules' => $rules]);
    }

    public function create()
    {
        return \Inertia\Inertia::render('Client/Settings/Automations/Create');
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

        $validated['user_id'] = auth()->id() ?? 1; // Fallback for dev without auth

        \App\Models\AutomationRule::create($validated);

        return redirect()->route('settings.automations.index')->with('success', 'Automation rule created.');
    }

    public function edit(\App\Models\AutomationRule $automationRule)
    {
        return \Inertia\Inertia::render('Client/Settings/Automations/Edit', ['rule' => $automationRule]);
    }

    public function update(Request $request, \App\Models\AutomationRule $automationRule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'event_trigger' => 'required|string|max:255',
            'conditions' => 'nullable|array',
            'actions' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $automationRule->update($validated);

        return redirect()->route('settings.automations.index')->with('success', 'Automation rule updated.');
    }

    public function destroy(\App\Models\AutomationRule $automationRule)
    {
        $automationRule->delete();

        return redirect()->route('settings.automations.index')->with('success', 'Automation rule deleted.');
    }
}
