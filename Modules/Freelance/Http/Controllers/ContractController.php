<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Contract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityService;
use Modules\Freelance\Domains\Contract\Actions\CompleteContractAction;
use Illuminate\Support\Facades\Gate;

class ContractController extends Controller
{
    public function __construct(private CompleteContractAction $completeContractAction) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $contracts = Contract::where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                  ->orWhere('freelancer_id', $user->id);
            })
            ->with(['job:id,title,type,budget,currency_id', 'client:id,name', 'freelancer:id,name'])
            ->latest()
            ->paginate(15);

        $stats = [
            'total'     => Contract::where('client_id', $user->id)->orWhere('freelancer_id', $user->id)->count(),
            'active'    => Contract::where(function ($q) use ($user) {
                $q->where('client_id', $user->id)->orWhere('freelancer_id', $user->id);
            })->where('status', 'active')->count(),
            'completed' => Contract::where(function ($q) use ($user) {
                $q->where('client_id', $user->id)->orWhere('freelancer_id', $user->id);
            })->where('status', 'completed')->count(),
            'disputed'  => Contract::where(function ($q) use ($user) {
                $q->where('client_id', $user->id)->orWhere('freelancer_id', $user->id);
            })->where('status', 'disputed')->count(),
            'total_value' => Contract::where('client_id', $user->id)
                ->orWhere('freelancer_id', $user->id)
                ->where('status', 'completed')
                ->sum('amount'),
        ];

        return Inertia::render('Freelance/Contracts/Index', [
            'contracts' => $contracts,
            'stats'     => $stats,
        ]);
    }

    public function show(Contract $contract)
    {
        Gate::authorize('view', $contract);

        $contract->load(['job.client', 'freelancer']);
        return Inertia::render('Freelance/Contracts/Show', ['contract' => $contract]);
    }

    public function complete(Request $request, Contract $contract)
    {
        Gate::authorize('complete', $contract);

        try {
            $this->completeContractAction->execute($contract, $request->user());
            return back()->with('success', __('general.contract_marked_as_completed_and_funds_paid_to_freelancer'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Payout failed: ' . $e->getMessage()]);
        }
    }

    public function dispute(Request $request, Contract $contract)
    {
        $user = $request->user();
        if ($contract->client_id !== $user->id && $contract->freelancer_id !== $user->id) {
            abort(403);
        }

        $contract->update(['status' => 'disputed']);
        // Here you would typically notify admins or trigger a dispute resolution process.

        return back()->with('success', __('general.contract_dispute_initiated'));
    }
}
