<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Freelance\Models\Contract;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Modules\Freelance\Domains\Finance\Actions\AddPointsAction;

class FreelanceContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with(['job.currency', 'client', 'freelancer', 'currency']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('job', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            })->orWhereHas('client', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('freelancer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        $contracts = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Freelance/Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only('search', 'status')
        ]);
    }

    public function show(Contract $contract)
    {
        $contract->load(['job.currency', 'proposal.currency', 'client', 'freelancer', 'currency']);

        return Inertia::render('Admin/Freelance/Contracts/Show', [
            'contract' => $contract
        ]);
    }

    public function updateStatus(Request $request, Contract $contract)
    {
        $request->validate([
            'status' => 'required|string|in:active,completed,cancelled,suspended,delivered'
        ]);

        $contract->update(['status' => $request->status]);

        return back()->with('success', __('freelance.contract_status_updated'));
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return redirect()->route('admin.freelance.contracts.index')->with('success', __('freelance.contract_deleted'));
    }
}
