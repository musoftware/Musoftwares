<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\ClientWallet;
use Modules\ERP\Models\WalletTransaction as ClientWalletTransaction;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Inertia\Inertia;

/**
 * ERP Client Wallet Controller.
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  FINANCIAL ISOLATION BOUNDARY                                    ║
 * ║                                                                  ║
 * ║  This controller ONLY operates on ERP-internal client wallets    ║
 * ║  (table: client_wallets / client_wallet_transactions).           ║
 * ║                                                                  ║
 * ║  It MUST NEVER touch:                                            ║
 * ║    • App\Models\Wallet          (table: wallets)        ║
 * ║    • App\Models\WalletTransaction (wallet_transactions) ║
 * ║    • App\Http\Controllers\FinancialController (real money)       ║
 * ║                                                                  ║
 * ║  ERP client wallets are tenant-managed bookkeeping ledgers,      ║
 * ║  NOT real platform money. They represent credit that the tenant  ║
 * ║  grants to their clients for invoice payment purposes only.      ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */
class WalletController extends Controller
{
    // ── Tenant resolution helper ─────────────────────────────────────

    /**
     * Resolve the current tenant and ensure the given client belongs to it.
     * Aborts with 403 if ownership cannot be confirmed.
     */
    private function resolveTenantAndClient(int|string $clientId): array
    {
        $user   = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();

        $client = TenantClient::with('currency')->where('tenant_id', $tenant->id)
            ->findOrFail($clientId);

        return [$tenant, $client];
    }

    // ── Show wallet ──────────────────────────────────────────────────

    public function show(Request $request, int $client)
    {
        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        $wallet = [
            'balance'        => $clientModel->balance(),
            'locked_balance' => $clientModel->lockedBalance(),
            'currency_id'    => $clientModel->currency_id,
            'currency'       => $clientModel->currency,
        ];

        $transactions = ClientWalletTransaction::where('client_id', $clientModel->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('ERP/Wallet/Show', [
            'wallet'       => $wallet,
            'transactions' => $transactions,
            'client'       => $clientModel,
        ]);
    }

    // ── Adjust wallet ──────────────────────────────────────────────────

    public function adjust(Request $request, int $client)
    {
        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        $wallet = [
            'balance'        => $clientModel->balance(),
            'locked_balance' => $clientModel->lockedBalance(),
            'currency_id'    => $clientModel->currency_id,
            'currency'       => $clientModel->currency,
        ];

        return Inertia::render('ERP/Wallet/Adjust', [
            'wallet' => $wallet,
            'client' => $clientModel,
        ]);
    }

    // ── Transaction history (JSON) ────────────────────────────────────

    public function transactions(Request $request, int $client)
    {
        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        $query = ClientWalletTransaction::where('client_id', $clientModel->id);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }

        return response()->json($query->latest()->paginate(20));
    }

    // ── Manual credit (tenant adds credit to client's ERP wallet) ────

    public function manualCredit(Request $request, int $client)
    {
        $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'note'       => 'required|string|max:500',
            'project_id' => 'nullable|exists:erp_projects,id',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            $projectId = $request->input('project_id');
            if ($projectId) {
                $project = \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->findOrFail($projectId);
                if ($project->client_id !== $clientModel->id) {
                    throw new \Exception(__('errors.project_client_mismatch'));
                }
            }

            DB::transaction(function () use ($request, $tenant, $clientModel, $projectId) {
                $amount = (float) $request->input('amount');

                $businessCurrencyId = $tenant->base_currency_id;
                $businessAmount = \App\Models\CurrenciesExchange::RateByDate(
                    now(),
                    $amount,
                    $clientModel->currency_id,
                    $businessCurrencyId
                );

                ClientWalletTransaction::create([
                    'tenant_id'        => $tenant->id,
                    'client_id'        => $clientModel->id,
                    'project_id'       => $projectId,
                    'type'             => 'manual_credit',
                    'direction'        => 'credit',
                    'amount'           => $amount,
                    'currency_id'      => $clientModel->currency_id,
                    'business_amount'  => $businessAmount,
                    'business_currency_id' => $businessCurrencyId,
                    'exchange_rate'    => \App\Models\CurrenciesExchange::Rate(now()->toDateString(), $clientModel->currency_id, $businessCurrencyId),
                    'exchange_rate_date'=> now()->toDateString(),
                    'reference_type'   => 'manual_credit',
                    'reference_id'     => Auth::id(),
                    'note'             => 'Credit: ' . $request->input('note'),
                    'created_by'       => Auth::id(),
                ]);
            });

            if ($projectId) {
                return redirect()->route('erp.projects.show', $projectId)
                    ->with('success', __('erp.client_credit_added_success'));
            }

            return back()->with('success', __('erp.client_credit_added_success'));
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    // ── Manual debit (tenant removes credit from client's ERP wallet) ─

    public function manualDebit(Request $request, int $client)
    {
        $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'note'       => 'required|string|max:500',
            'project_id' => 'nullable|exists:erp_projects,id',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            $projectId = $request->input('project_id');
            if ($projectId) {
                $project = \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->findOrFail($projectId);
                if ($project->client_id !== $clientModel->id) {
                    throw new \Exception(__('errors.project_client_mismatch'));
                }
            }

            DB::transaction(function () use ($request, $tenant, $clientModel, $projectId) {
                $amount = (float) $request->input('amount');

                if ($clientModel->balance() < $amount) {
                    throw new \Exception(__('errors.insufficient_client_balance'));
                }

                $businessCurrencyId = $tenant->base_currency_id;
                $businessAmount = \App\Models\CurrenciesExchange::RateByDate(
                    now(),
                    $amount,
                    $clientModel->currency_id,
                    $businessCurrencyId
                );

                ClientWalletTransaction::create([
                    'tenant_id'        => $tenant->id,
                    'client_id'        => $clientModel->id,
                    'project_id'       => $projectId,
                    'type'             => 'manual_debit',
                    'direction'        => 'debit',
                    'amount'           => $amount,
                    'currency_id'      => $clientModel->currency_id,
                    'business_amount'  => $businessAmount,
                    'business_currency_id' => $businessCurrencyId,
                    'exchange_rate'    => \App\Models\CurrenciesExchange::Rate(now()->toDateString(), $clientModel->currency_id, $businessCurrencyId),
                    'exchange_rate_date'=> now()->toDateString(),
                    'reference_type'   => 'manual_debit',
                    'reference_id'     => Auth::id(),
                    'note'             => 'Debit: ' . $request->input('note'),
                    'created_by'       => Auth::id(),
                ]);
            });

            if ($projectId) {
                return redirect()->route('erp.projects.show', $projectId)
                    ->with('success', __('erp.client_debit_recorded_success'));
            }

            return back()->with('success', __('erp.client_debit_recorded_success'));
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

}
