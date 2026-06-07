<?php

namespace Modules\Booking\app\Features\BookingRules\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRule;

class BookingAdvancedRulesController extends Controller
{
    public function index(Request $request)
    {
        // Tenant scope is typically applied via middleware or global scope
        $rules = BookingAdvancedRule::with(['conditions', 'actions'])
            ->orderBy('priority', 'desc')
            ->get();
            
        return response()->json(['data' => $rules]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'event_trigger' => 'required|string',
            'priority' => 'integer',
            'conditions' => 'required|array',
            'actions' => 'required|array',
        ]);

        // In a real implementation, we'd use a repository/service with DB transaction
        $rule = BookingAdvancedRule::create([
            'tenant_id' => 1, // Mock tenant
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'event_trigger' => $validated['event_trigger'],
            'priority' => $validated['priority'] ?? 0,
        ]);

        foreach ($validated['conditions'] as $condition) {
            $condition['tenant_id'] = 1;
            $rule->conditions()->create($condition);
        }

        foreach ($validated['actions'] as $action) {
            $action['tenant_id'] = 1;
            $rule->actions()->create($action);
        }

        return response()->json(['data' => $rule->load(['conditions', 'actions'])], 201);
    }

    public function show(BookingAdvancedRule $rule)
    {
        return response()->json(['data' => $rule->load(['conditions', 'actions'])]);
    }

    public function update(Request $request, BookingAdvancedRule $rule)
    {
        // Update logic here
        return response()->json(['data' => $rule]);
    }

    public function destroy(BookingAdvancedRule $rule)
    {
        $rule->delete();
        return response()->json(null, 204);
    }
}
