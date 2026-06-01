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
        $query = Contract::with(['job', 'client', 'freelancer']);

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
        $contract->load(['job', 'proposal', 'client', 'freelancer']);

        return Inertia::render('Admin/Freelance/Contracts/Show', [
            'contract' => $contract
        ]);
    }

    public function updateStatus(Request $request, Contract $contract)
    {
        $request->validate([
            'status' => 'required|string|in:active,completed,cancelled,disputed'
        ]);

        $contract->update(['status' => $request->status]);

        return back()->with('success', __('freelance.contract_status_updated'));
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return redirect()->route('admin.freelance.contracts.index')->with('success', __('freelance.contract_deleted'));
    }

    public function resolveDispute(Request $request, Contract $contract, AddPointsAction $addPointsAction)
    {
        $request->validate([
            'resolution' => 'required|string|in:refund_client,pay_freelancer,split'
        ]);

        if ($contract->status !== 'disputed') {
            return back()->with('error', __('freelance.contract_not_disputed'));
        }

        $points = $contract->contract_points;

        try {
            DB::transaction(function () use ($request, $contract, $points, $addPointsAction) {
                if ($request->resolution === 'refund_client') {
                    $addPointsAction->execute($contract->client_id, $points, __('freelance.dispute_refund'), 'contract_dispute', $contract->id);
                    $contract->update(['status' => 'cancelled']);
                } elseif ($request->resolution === 'pay_freelancer') {
                    $addPointsAction->execute($contract->freelancer_id, $points, __('freelance.dispute_payment'), 'contract_dispute', $contract->id);
                    $contract->update(['status' => 'completed', 'completed_at' => now()]);
                } elseif ($request->resolution === 'split') {
                    $half = intval($points / 2);
                    $remainder = $points - $half;
                    $addPointsAction->execute($contract->client_id, $half, __('freelance.dispute_split_refund'), 'contract_dispute', $contract->id);
                    $addPointsAction->execute($contract->freelancer_id, $remainder, __('freelance.dispute_split_payment'), 'contract_dispute', $contract->id);
                    $contract->update(['status' => 'cancelled']);
                }
            });

            return back()->with('success', __('freelance.dispute_resolved_success'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
