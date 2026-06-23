<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Models\WalletTransaction;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Services\WalletService;
use Inertia\Inertia;

/**
 * ERP Client Transaction Controller.
 *
 * Manages direct client transactions (receive, send, refund).
 * Balance is computed dynamically from transactions.
 * Locked balance is computed from unpaid invoices.
 *
 * No intermediate wallet model is used — transactions are the
 * single source of truth per erp-financial-rules.
 */
class WalletController extends Controller
{
    private WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }
    // ── Tenant resolution helper ─────────────────────────────────────

    /**
     * Resolve the current tenant and ensure the given client belongs to it.
     * Aborts with 403 if ownership cannot be confirmed.
     */
    private function resolveTenantAndClient(int|string $clientId): array
    {
        $user   = auth('erp_team')->user();
        $tenant = auth('erp_team')->user()->tenant;

        $client = TenantClient::with('currency')->where('tenant_id', $tenant->id)
            ->findOrFail($clientId);

        return [$tenant, $client];
    }

    /**
     * Build the wallet summary array from computed balance/lockedBalance.
     */
    private function buildWalletSummary(TenantClient $client): array
    {
        return [
            'balance'        => $client->balance(),
            'locked_balance' => $client->lockedBalance(),
            'currency_id'    => $client->currency_id,
            'currency'       => $client->currency,
        ];
    }

    // ── Show ledger ─────────────────────────────────────────────────

    public function show(Request $request, int $client)
    {
        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        $wallet = $this->buildWalletSummary($clientModel);

        $transactions = WalletTransaction::where('client_id', $clientModel->id)
            ->with(['currency', 'project', 'creator'])
            ->latest()
            ->paginate(20);

        return Inertia::render('ERP/Wallet/Show', [
            'wallet'       => $wallet,
            'transactions' => $transactions,
            'client'       => $clientModel,
        ]);
    }

    // ── Adjust form page ────────────────────────────────────────────

    public function adjust(Request $request, int $client)
    {
        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        $wallet = $this->buildWalletSummary($clientModel);

        return Inertia::render('ERP/Wallet/Adjust', [
            'wallet' => $wallet,
            'client' => $clientModel,
        ]);
    }

    // ── Receive payment (client pays → creates 'received' transaction) ─

    public function receivePayment(Request $request, int $client)
    {
        $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'note'       => 'nullable|string|max:500',
            'project_id' => 'nullable|exists:erp_projects,id',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            $projectId = $request->input('project_id');
            if ($projectId) {
                $project = \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->findOrFail($projectId);
                if ($project->client_id !== $clientModel->id) {
                    throw new \Exception(__('erp.project_client_mismatch'));
                }
            }

            DB::transaction(function () use ($request, $tenant, $clientModel, $projectId) {
                $amount = number_format($request->input('amount'), 2, '.', '');

                $this->walletService->createTransaction(
                    $clientModel,
                    'received',
                    'credit',
                    $amount,
                    $tenant->id,
                    $projectId,
                    $request->input('note') ?? __('erp.manual_wallet_transaction'),
                    'manual_receive'
                );
            });

            if ($projectId) {
                return redirect()->route('erp.projects.show', $projectId)
                    ->with('success', __('erp.payment_received_success'));
            }

            return back()->with('success', __('erp.payment_received_success'));
        } catch (\Exception $e) {
            Log::error("Failed to receive payment for client {$clientModel->id}: " . $e->getMessage());
            return back()->withErrors(['amount' => __('erp.transaction_failed')]);
        }
    }

    // ── Send payment (deduct from client → creates 'sent' transaction with negative amount) ─

    public function sendPayment(Request $request, int $client)
    {
        $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'note'       => 'nullable|string|max:500',
            'project_id' => 'nullable|exists:erp_projects,id',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            $projectId = $request->input('project_id');
            if ($projectId) {
                $project = \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->findOrFail($projectId);
                if ($project->client_id !== $clientModel->id) {
                    throw new \Exception(__('erp.project_client_mismatch'));
                }
            }

            DB::transaction(function () use ($request, $tenant, $clientModel, $projectId) {
                $amount = number_format($request->input('amount'), 2, '.', '');

                if ($clientModel->balance() < $amount) {
                    throw new \Exception(__('erp.insufficient_client_balance'));
                }

                $this->walletService->createTransaction(
                    $clientModel,
                    'sent',
                    'debit',
                    $amount,
                    $tenant->id,
                    $projectId,
                    $request->input('note') ?? __('erp.manual_wallet_transaction'),
                    'manual_send'
                );
            });

            if ($projectId) {
                return redirect()->route('erp.projects.show', $projectId)
                    ->with('success', __('erp.payment_sent_success'));
            }

            return back()->with('success', __('erp.payment_sent_success'));
        } catch (\Exception $e) {
            Log::error("Failed to send payment for client {$clientModel->id}: " . $e->getMessage());
            return back()->withErrors(['amount' => __('erp.transaction_failed')]);
        }
    }

    // ── Refund (refund to client → creates 'refunded' transaction with negative amount) ─

    public function refund(Request $request, int $client)
    {
        $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'note'       => 'nullable|string|max:500',
            'project_id' => 'nullable|exists:erp_projects,id',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            $projectId = $request->input('project_id');
            if ($projectId) {
                $project = \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->findOrFail($projectId);
                if ($project->client_id !== $clientModel->id) {
                    throw new \Exception(__('erp.project_client_mismatch'));
                }
            }

            DB::transaction(function () use ($request, $tenant, $clientModel, $projectId) {
                $amount = number_format($request->input('amount'), 2, '.', '');

                $this->walletService->createTransaction(
                    $clientModel,
                    'refunded',
                    'debit',
                    $amount,
                    $tenant->id,
                    $projectId,
                    $request->input('note') ?? __('erp.manual_wallet_transaction'),
                    'manual_refund'
                );
            });

            if ($projectId) {
                return redirect()->route('erp.projects.show', $projectId)
                    ->with('success', __('erp.refund_processed_success'));
            }

            return back()->with('success', __('erp.refund_processed_success'));
        } catch (\Exception $e) {
            Log::error("Failed to refund for client {$clientModel->id}: " . $e->getMessage());
            return back()->withErrors(['amount' => __('erp.transaction_failed')]);
        }
    }

    // ── Earn (Add bonus to client → creates 'earned' transaction with positive amount) ──

    public function addBonus(Request $request, int $client)
    {
        $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'note'       => 'nullable|string|max:500',
            'project_id' => 'nullable|exists:erp_projects,id',
        ]);

        [$tenant, $clientModel] = $this->resolveTenantAndClient($client);

        try {
            $projectId = $request->input('project_id');
            if ($projectId) {
                $project = \Modules\ERP\Models\Project::where('tenant_id', $tenant->id)->findOrFail($projectId);
                if ($project->client_id !== $clientModel->id) {
                    throw new \Exception(__('erp.project_client_mismatch'));
                }
            }

            DB::transaction(function () use ($request, $tenant, $clientModel, $projectId) {
                $amount = number_format($request->input('amount'), 2, '.', '');

                $this->walletService->createTransaction(
                    $clientModel,
                    'earned',
                    'credit',
                    $amount,
                    $tenant->id,
                    $projectId,
                    $request->input('note') ?? __('erp.manual_wallet_transaction'),
                    'manual_bonus'
                );
            });

            if ($projectId) {
                return redirect()->route('erp.projects.show', $projectId)
                    ->with('success', __('erp.bonus_added_success'));
            }

            return back()->with('success', __('erp.bonus_added_success'));
        } catch (\Exception $e) {
            Log::error("Failed to add bonus for client {$clientModel->id}: " . $e->getMessage());
            return back()->withErrors(['amount' => __('erp.transaction_failed')]);
        }
    }
}
