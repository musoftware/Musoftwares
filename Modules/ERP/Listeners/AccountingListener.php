<?php

namespace Modules\ERP\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\ERP\Services\AccountingService;
use Modules\ERP\Models\Accounting\LedgerAccount;
use App\Events\InvoicePaid;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Events\AmountReceived;

class AccountingListener
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function handle(object $event): void
    {
        if ($event instanceof InvoicePaid) {
            $this->handleInvoicePaid($event);
        } elseif ($event instanceof WalletCredited) {
            $this->handleWalletCredited($event);
        } elseif ($event instanceof WalletDebited) {
            $this->handleWalletDebited($event);
        } elseif ($event instanceof AmountReceived) {
            $this->handleAmountReceived($event);
        }
    }

    protected function getTenantId(): int
    {
        $tenantId = app(\Modules\ERP\Infrastructure\Context\TenantContext::class)->getTenantId();
        if (!$tenantId && session()->has('tenant_id')) {
            $tenantId = session('tenant_id');
        }
        if (!$tenantId && auth('erp_team')->check()) {
            $tenantId = auth('erp_team')->user()->tenant_id;
        }
        return $tenantId ?? throw new \Exception('No tenant context found in AccountingListener');
    }

    protected function handleInvoicePaid(InvoicePaid $event)
    {
        $tenantId = $this->getTenantId();
        $cashAccount = $this->accountingService->getOrCreateAccount($tenantId, '1000', 'Cash/Bank', 'asset');
        $arAccount = $this->accountingService->getOrCreateAccount($tenantId, '1200', 'Accounts Receivable', 'asset');
        
        $amount = $event->invoice->total ?? 0;
        if ($amount > 0) {
            $this->accountingService->recordTransaction(
                'INV-PAY-' . $event->invoice->id,
                'Invoice Payment Received',
                now()->toDateString(),
                [
                    ['ledger_account_id' => $cashAccount->id, 'debit' => $amount, 'credit' => 0],
                    ['ledger_account_id' => $arAccount->id, 'debit' => 0, 'credit' => $amount],
                ],
                $tenantId
            );
        }
    }

    protected function handleWalletCredited(WalletCredited $event)
    {
        $tenantId = $this->getTenantId();
        $cashAccount = $this->accountingService->getOrCreateAccount($tenantId, '1000', 'Cash/Bank', 'asset');
        $liabilityAccount = $this->accountingService->getOrCreateAccount($tenantId, '2000', 'Wallet Balances', 'liability');

        if ($event->amount > 0) {
            $this->accountingService->recordTransaction(
                'WAL-CREDIT-' . ($event->wallet->id ?? uniqid()),
                'Wallet Credited',
                now()->toDateString(),
                [
                    ['ledger_account_id' => $cashAccount->id, 'debit' => $event->amount, 'credit' => 0],
                    ['ledger_account_id' => $liabilityAccount->id, 'debit' => 0, 'credit' => $event->amount],
                ],
                $tenantId
            );
        }
    }

    protected function handleWalletDebited(WalletDebited $event)
    {
        $tenantId = $this->getTenantId();
        $liabilityAccount = $this->accountingService->getOrCreateAccount($tenantId, '2000', 'Wallet Balances', 'liability');
        $revenueAccount = $this->accountingService->getOrCreateAccount($tenantId, '4000', 'Sales Revenue', 'revenue');

        if ($event->amount > 0) {
            $this->accountingService->recordTransaction(
                'WAL-DEBIT-' . ($event->wallet->id ?? uniqid()),
                'Wallet Debited',
                now()->toDateString(),
                [
                    ['ledger_account_id' => $liabilityAccount->id, 'debit' => $event->amount, 'credit' => 0],
                    ['ledger_account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => $event->amount],
                ],
                $tenantId
            );
        }
    }

    protected function handleAmountReceived(AmountReceived $event)
    {
        $tenantId = $this->getTenantId();
        $cashAccount = $this->accountingService->getOrCreateAccount($tenantId, '1000', 'Cash/Bank', 'asset');
        $revenueAccount = $this->accountingService->getOrCreateAccount($tenantId, '4000', 'Sales Revenue', 'revenue');

        if ($event->amount > 0) {
            $this->accountingService->recordTransaction(
                'AR-' . uniqid(),
                'Amount received from event',
                now()->toDateString(),
                [
                    ['ledger_account_id' => $cashAccount->id, 'debit' => $event->amount, 'credit' => 0],
                    ['ledger_account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => $event->amount],
                ],
                $tenantId
            );
        }
    }
}
