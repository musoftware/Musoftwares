<?php

namespace Modules\Freelance\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Freelance\Models\Contract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
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
