<?php

namespace App\Http\Controllers;

use App\Models\AutomationRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AutomationRuleController extends Controller
{
    public function index()
    {
        $rules = AutomationRule::where('user_id', auth()->id())->get();

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

        $validated['user_id'] = auth()->id() ?? 1; // Fallback for dev without auth

        AutomationRule::create($validated);

        return redirect()->route('settings.automations.index')->with('success', 'Automation rule created.');
    }

    public function edit(AutomationRule $automationRule)
    {
        return Inertia::render('Client/Settings/Automations/Edit', ['rule' => $automationRule]);
    }

    public function update(Request $request, AutomationRule $automationRule)
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

    public function destroy(AutomationRule $automationRule)
    {
        $automationRule->delete();

        return redirect()->route('settings.automations.index')->with('success', 'Automation rule deleted.');
    }
}
