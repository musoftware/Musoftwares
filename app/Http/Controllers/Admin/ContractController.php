<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing\PlatformContract;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = PlatformContract::with('user');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $contracts = $query->latest()->paginate(20);

        return Inertia::render('Admin/Contracts/Index', [
            'contracts' => $contracts,
            'currentTab' => $status,
        ]);
    }

    public function updateStatus(Request $request, PlatformContract $contract)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:draft,sent,signed,cancelled',
        ]);

        $contract->status = $validated['status'];
        
        // If marking as signed, automatically stamp the time if not already
        if ($validated['status'] === 'signed' && is_null($contract->signed_at)) {
            $contract->signed_at = now();
        }

        $contract->save();

        return redirect()->back()->with('success', 'Contract status updated.');
    }

    public function destroy(PlatformContract $contract)
    {
        $contract->delete();

        return redirect()->back()->with('success', 'Contract deleted successfully.');
    }
}
