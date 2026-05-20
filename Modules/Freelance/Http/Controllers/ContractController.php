<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Contract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\ActivityService;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $contracts = Contract::where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                  ->orWhere('freelancer_id', $user->id);
            })
            ->with(['job:id,title,type,budget,currency_code', 'client:id,name', 'freelancer:id,name'])
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

    public function show(Request $request, Contract $contract)
    {
        $user = $request->user();
        if ($contract->client_id !== $user->id && $contract->freelancer_id !== $user->id) {
            abort(403);
        }

        $contract->load(['job', 'proposal', 'client', 'freelancer']);
        return Inertia::render('Freelance/Contracts/Show', ['contract' => $contract]);
    }

    public function complete(Request $request, Contract $contract, \Modules\Core\Services\FinancialTransactionService $financialService)
    {
        if ($contract->client_id !== $request->user()->id) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($contract, $financialService) {
                $contract->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                $contract->job->update(['status' => 'completed']);

                // Retrieve or create wallets for double-entry ledger transactions
                $clientWallet = \Modules\Core\Models\Wallet::firstOrCreate(
                    ['owner_type' => \App\Models\User::class, 'owner_id' => $contract->client_id],
                    ['context' => 'user', 'balance' => 10000.00, 'currency' => 'USD']
                );

                $freelancerWallet = \Modules\Core\Models\Wallet::firstOrCreate(
                    ['owner_type' => \App\Models\User::class, 'owner_id' => $contract->freelancer_id],
                    ['context' => 'user', 'balance' => 0.00, 'currency' => 'USD']
                );

                $financialService->transferWalletFunds(
                    $clientWallet->id,
                    $freelancerWallet->id,
                    $contract->amount,
                    "Payout for contract #{$contract->id}: {$contract->job->title}",
                    Contract::class,
                    (string)$contract->id
                );
            });

            ActivityService::log(
                event: 'contract.completed',
                description: "Contract completed for job: {$contract->job->title}",
                subject: $contract,
                workspace: 'freelance'
            );

            return back()->with('success', 'Contract marked as completed and funds paid to freelancer.');
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

        return back()->with('success', 'Contract dispute initiated.');
    }
}
