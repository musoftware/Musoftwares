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
use App\Traits\ConvertsCurrency;

class ContractController extends Controller
{
    use ConvertsCurrency;
    public function __construct(private CompleteContractAction $completeContractAction) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $userCurrencyId = (int) $user->currency_id;

        $contracts = Contract::where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                  ->orWhere('freelancer_id', $user->id);
            })
            ->with(['job:id,title,type,budget,currency_id', 'job.currency', 'client:id,name', 'freelancer:id,name'])
            ->latest()
            ->paginate(15);

        $contracts->through(function ($contract) use ($userCurrencyId) {
            // Convert contract amount
            $date = $contract->started_at ?? $contract->created_at;
            $this->convertModelCurrency($contract, 'amount', 'currency_id', $userCurrencyId, $date ? (string) $date : null);

            // Convert nested job budget
            if ($contract->relationLoaded('job') && $contract->job) {
                $this->convertModelCurrency($contract->job, 'budget', 'currency_id', $userCurrencyId);
            }

            return $contract;
        });

        // Sum total_value with per-contract date-based exchange
        $completedContracts = Contract::where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                  ->orWhere('freelancer_id', $user->id);
            })
            ->where('status', 'completed')
            ->get(['amount', 'currency_id', 'started_at', 'created_at']);

        $totalValue = 0.0;
        foreach ($completedContracts as $c) {
            $date = $c->started_at ?? $c->created_at;
            $totalValue += $this->convertToUserCurrency(
                (float) $c->amount,
                (int) $c->currency_id,
                $userCurrencyId,
                $date ? (string) $date : null
            );
        }

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
            'total_value' => $totalValue,
            'isFiat'      => \Illuminate\Support\Facades\Schema::hasColumn('freelance_contracts', 'amount'),
        ];

        return Inertia::render('Freelance/Contracts/Index', [
            'contracts'    => $contracts,
            'stats'        => $stats,
            'userCurrency' => $this->currencyForFrontend($userCurrencyId),
        ]);
    }

    public function show(Request $request, Contract $contract)
    {
        Gate::authorize('view', $contract);

        $contract->load(['job.client', 'freelancer', 'job.currency']);
        
        $userCurrencyId = (int) $request->user()->currency_id;
        $date = $contract->started_at ?? $contract->created_at;
        $this->convertModelCurrency($contract, 'amount', 'currency_id', $userCurrencyId, $date ? (string) $date : null);

        if ($contract->relationLoaded('job') && $contract->job) {
            $this->convertModelCurrency($contract->job, 'budget', 'currency_id', $userCurrencyId);
        }

        return Inertia::render('Freelance/Contracts/Show', [
            'contract'     => $contract,
            'userCurrency' => $this->currencyForFrontend($userCurrencyId),
        ]);
    }

    public function complete(Request $request, Contract $contract)
    {
        Gate::authorize('complete', $contract);

        try {
            $this->completeContractAction->execute($contract, $request->user());
            return back()->with('success', __('general.contract_marked_as_completed_successfully'));
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
