<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function create()
    {
        return Inertia::render('ERP/Expenses/Create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'date' => 'nullable|date',
        ]);

        // TODO: Create expense record once model/migration is ready

        return back()->with('success', 'Expense recorded.');
    }
}
