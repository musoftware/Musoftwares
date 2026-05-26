<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Searchable;

class Invoice extends TenantModel
{
    protected $table = 'erp_invoices';

    use Searchable;
    protected $fillable = [
        'tenant_id', 'invoice_number', 'client_id', 'project_id', 'status',
        'amount', 'amount_currency', 'business_amount', 'business_currency',
        'exchange_rate', 'exchange_rate_date', 'discount_amount', 'tax_rate', 'tax_amount',
        'paid_amount', 'due_date', 'issued_at', 'paid_at', 'notes', 'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function platformClient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function tenantClient(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function getClientAttribute()
    {
        return (empty($this->tenant_id) || $this->tenant_id === Tenant::platformId())
            ? $this->platformClient 
            : $this->tenantClient;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(InvoiceCost::class);
    }

    public function referralEarnings(): HasMany
    {
        return $this->hasMany(ReferralEarning::class);
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    // ── Computed Attributes ──────────────────────────────────────

    /**
     * Remaining unpaid amount.
     * Recovered from old project: Invoice::unpaid_total()
     */
    public function unpaidAmount(): float
    {
        return round(max(0, (float) $this->amount - (float) $this->paid_amount), 2);
    }

    /**
     * Subtotal before tax/discount (sum of item line totals).
     * Recovered from old project: Invoice::sub_total()
     */
    public function subTotal(): float
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        return (float) $items->sum('total');
    }

    /**
     * Total internal cost for this invoice.
     * Recovered from old project: Invoice::totalInternalCost()
     */
    public function totalCost(): float
    {
        $costs = $this->relationLoaded('costs') ? $this->costs : $this->costs()->get();
        return (float) $costs->sum('amount');
    }

    /**
     * Revenue (total minus costs).
     * Recovered from old project: Invoice::revenue()
     */
    public function revenue(): float
    {
        return round((float) $this->amount - $this->totalCost(), 2);
    }

    /**
     * Whether this invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && in_array($this->status, ['sent', 'partial']);
    }

    // ── Status Helpers ───────────────────────────────────────────

    public function isPaid(): bool { return $this->status === 'paid'; }
    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function canBeBilled(): bool { return in_array($this->status, ['sent', 'partial']); }

    // ── Payment Lifecycle ────────────────────────────────────────
    // Recovered from old project: Invoice::bill_invoice(),
    //     Invoice::partially_bill_invoice(), Invoice::cancel_invoice()

    /**
     * Bill the invoice in full from the client's wallet.
     * Creates wallet transactions and marks the invoice as paid.
     *
     * Recovered from old project: Invoice::bill_invoice()
     *
     * @return array{ok: bool, message?: string}
     */
    public function billInvoice(): array
    {
        if ($this->status === 'paid') {
            return ['ok' => false, 'message' => 'Invoice is already paid.'];
        }

        if (!in_array($this->status, ['sent', 'partial'])) {
            return ['ok' => false, 'message' => 'Invoice must be sent before it can be billed.'];
        }

        $client = $this->client;
        if (!$client) {
            return ['ok' => false, 'message' => 'Invoice has no associated client.'];
        }

        $wallet = $client->wallet;
        if (!$wallet) {
            $wallet = ClientWallet::create([
                'tenant_id' => $this->tenant_id,
                'client_id' => $client->id,
                'balance' => 0,
                'currency' => $this->amount_currency ?? 'USD',
            ]);
        }

        $amountDue = $this->unpaidAmount();
        if ($amountDue <= 0) {
            return ['ok' => false, 'message' => 'No outstanding balance on this invoice.'];
        }

        if ((float) $wallet->balance < $amountDue) {
            return ['ok' => false, 'message' => 'Insufficient client wallet balance. Required: ' . $amountDue . ', Available: ' . $wallet->balance];
        }

        return DB::transaction(function () use ($wallet, $amountDue, $client) {
            // Lock the wallet row for update
            $wallet = ClientWallet::where('id', $wallet->id)->lockForUpdate()->first();

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore - $amountDue;

            // Debit the client wallet
            WalletTransaction::create([
                'tenant_id' => $this->tenant_id,
                'wallet_id' => $wallet->id,
                'type' => 'invoice_paid',
                'direction' => 'debit',
                'amount' => $amountDue,
                'amount_currency' => $this->amount_currency,
                'business_amount' => (float) $this->business_amount,
                'business_currency' => $this->business_currency ?? 'USD',
                'exchange_rate' => (float) $this->exchange_rate,
                'exchange_rate_date' => $this->exchange_rate_date ?? now()->toDateString(),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => Invoice::class,
                'reference_id' => $this->id,
                'note' => 'Payment for Invoice #' . $this->invoice_number,
                'created_by' => Auth::id(),
            ]);

            $wallet->update(['balance' => $balanceAfter]);

            // Mark invoice as paid
            $this->update([
                'status' => 'paid',
                'paid_amount' => $this->amount,
                'paid_at' => now(),
            ]);

            // Process referral commissions
            $this->processReferralCommissions();

            // Fire event
            if (class_exists(\App\Events\InvoicePaid::class)) {
                event(new \App\Events\InvoicePaid($this));
            }

            \Modules\ERP\Services\ActivityLogger::log(
                'invoice_paid',
                "Invoice #{$this->invoice_number} was paid in full ($amountDue).",
                $this,
                $client->id
            );

            return ['ok' => true, 'message' => 'Invoice paid successfully.'];
        });
    }

    /**
     * Partially bill the invoice.
     * Recovered from old project: Invoice::partially_bill_invoice()
     *
     * @param float $amount The partial payment amount
     * @return array{ok: bool, message?: string}
     */
    public function partiallyBillInvoice(float $amount): array
    {
        if (!in_array($this->status, ['sent', 'partial'])) {
            return ['ok' => false, 'message' => 'Invoice must be sent before partial payment.'];
        }

        $maxPayable = $this->unpaidAmount();
        if ($amount <= 0 || $amount > $maxPayable) {
            return ['ok' => false, 'message' => "Payment amount must be between 0.01 and {$maxPayable}."];
        }

        $client = $this->client;
        $wallet = $client?->wallet;
        if (!$wallet || (float) $wallet->balance < $amount) {
            return ['ok' => false, 'message' => 'Insufficient client wallet balance.'];
        }

        // If the partial amount equals the remaining, treat as full payment
        if (abs($amount - $maxPayable) < 0.01) {
            return $this->billInvoice();
        }

        return DB::transaction(function () use ($wallet, $amount) {
            $wallet = ClientWallet::where('id', $wallet->id)->lockForUpdate()->first();

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            // Proportion for business amount
            $ratio = $amount / max(0.01, (float) $this->amount);
            $businessAmount = round((float) $this->business_amount * $ratio, 2);

            WalletTransaction::create([
                'tenant_id' => $this->tenant_id,
                'wallet_id' => $wallet->id,
                'type' => 'invoice_paid',
                'direction' => 'debit',
                'amount' => $amount,
                'amount_currency' => $this->amount_currency,
                'business_amount' => $businessAmount,
                'business_currency' => $this->business_currency ?? 'USD',
                'exchange_rate' => (float) $this->exchange_rate,
                'exchange_rate_date' => $this->exchange_rate_date ?? now()->toDateString(),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => Invoice::class,
                'reference_id' => $this->id,
                'note' => 'Partial payment for Invoice #' . $this->invoice_number,
                'created_by' => Auth::id(),
            ]);

            $wallet->update(['balance' => $balanceAfter]);

            $newPaid = round((float) $this->paid_amount + $amount, 2);
            $this->update([
                'status' => 'partial',
                'paid_amount' => $newPaid,
            ]);

            \Modules\ERP\Services\ActivityLogger::log(
                'invoice_partially_paid',
                "Invoice #{$this->invoice_number} received a partial payment of {$amount}.",
                $this,
                $wallet->client_id
            );

            return ['ok' => true, 'message' => 'Partial payment of ' . $amount . ' recorded.'];
        });
    }

    /**
     * Cancel the invoice and reverse any payments.
     * Recovered from old project: Invoice::cancel_invoice()
     *
     * @return array{ok: bool, message?: string}
     */
    public function cancelInvoice(): array
    {
        if ($this->status === 'cancelled') {
            return ['ok' => false, 'message' => 'Invoice is already cancelled.'];
        }

        return DB::transaction(function () {
            $paidAmount = (float) $this->paid_amount;

            // Refund any paid amount back to the client wallet
            if ($paidAmount > 0) {
                $client = $this->client;
                $wallet = $client?->wallet;

                if ($wallet) {
                    $wallet = ClientWallet::where('id', $wallet->id)->lockForUpdate()->first();
                    $balanceBefore = (float) $wallet->balance;
                    $balanceAfter = $balanceBefore + $paidAmount;

                    $ratio = $paidAmount / max(0.01, (float) $this->amount);
                    $businessAmount = round((float) $this->business_amount * $ratio, 2);

                    WalletTransaction::create([
                        'tenant_id' => $this->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'invoice_refund',
                        'direction' => 'credit',
                        'amount' => $paidAmount,
                        'amount_currency' => $this->amount_currency,
                        'business_amount' => $businessAmount,
                        'business_currency' => $this->business_currency ?? 'USD',
                        'exchange_rate' => (float) $this->exchange_rate,
                        'exchange_rate_date' => $this->exchange_rate_date ?? now()->toDateString(),
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'reference_type' => Invoice::class,
                        'reference_id' => $this->id,
                        'note' => 'Refund for cancelled Invoice #' . $this->invoice_number,
                        'created_by' => Auth::id(),
                    ]);

                    $wallet->update(['balance' => $balanceAfter]);
                }

                // Cancel related referral earnings
                $this->referralEarnings()->update(['status' => 'cancelled']);
            }

            $this->update([
                'status' => 'cancelled',
                'paid_amount' => 0,
            ]);

            \Modules\ERP\Services\ActivityLogger::log(
                'invoice_cancelled',
                "Invoice #{$this->invoice_number} was cancelled" . ($paidAmount > 0 ? " and {$paidAmount} refunded." : "."),
                $this,
                $this->client_id
            );

            return ['ok' => true, 'message' => 'Invoice cancelled' . ($paidAmount > 0 ? ' and ' . $paidAmount . ' refunded.' : '.')];
        });
    }

    /**
     * Process referral commissions for paid invoices.
     * Recovered from old project: User::calc_ref() + referral commission logic
     */
    protected function processReferralCommissions(): void
    {
        $client = $this->client;
        if (!$client || !$client->referred_by) {
            return;
        }

        $referrer = TenantClient::find($client->referred_by);
        if (!$referrer) {
            return;
        }

        // Level 1 referral commission (configurable, default 5%)
        $commissionRate = (float) config('erp.referral_commission_l1', 5);
        $commissionAmount = round(((float) $this->amount * $commissionRate) / 100, 2);

        if ($commissionAmount <= 0) {
            return;
        }

        $businessCommission = round(((float) $this->business_amount * $commissionRate) / 100, 2);

        ReferralEarning::create([
            'tenant_id' => $this->tenant_id,
            'invoice_id' => $this->id,
            'referrer_id' => $referrer->id,
            'referee_id' => $client->id,
            'level' => 1,
            'amount' => $commissionAmount,
            'amount_currency' => $this->amount_currency,
            'business_amount' => $businessCommission,
            'business_currency' => $this->business_currency ?? 'USD',
            'exchange_rate' => (float) $this->exchange_rate,
            'exchange_rate_date' => $this->exchange_rate_date ?? now()->toDateString(),
            'commission_rate' => $commissionRate,
            'status' => 'pending',
        ]);

        // Credit the referrer's wallet
        $referrerWallet = $referrer->wallet;
        if ($referrerWallet) {
            $referrerWallet = ClientWallet::where('id', $referrerWallet->id)->lockForUpdate()->first();
            $balanceBefore = (float) $referrerWallet->balance;
            $balanceAfter = $balanceBefore + $commissionAmount;

            WalletTransaction::create([
                'tenant_id' => $this->tenant_id,
                'wallet_id' => $referrerWallet->id,
                'type' => 'commission_earned',
                'direction' => 'credit',
                'amount' => $commissionAmount,
                'amount_currency' => $this->amount_currency,
                'business_amount' => $businessCommission,
                'business_currency' => $this->business_currency ?? 'USD',
                'exchange_rate' => (float) $this->exchange_rate,
                'exchange_rate_date' => $this->exchange_rate_date ?? now()->toDateString(),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => Invoice::class,
                'reference_id' => $this->id,
                'note' => 'Referral commission (L1 ' . $commissionRate . '%) for Invoice #' . $this->invoice_number,
                'created_by' => Auth::id(),
            ]);

            $referrerWallet->update(['balance' => $balanceAfter]);
        }
    }

    // ── Scopes ───────────────────────────────────────────────────

    /**
     * Scope: unpaid invoices (sent + partial).
     * Recovered from old project: Invoice::UnpaidInvoices()
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['sent', 'partial']);
    }

    /**
     * Scope: overdue invoices for admin dashboard.
     * Recovered from old project: Invoice::scopeOverdueForAdminDashboard()
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['sent', 'partial'])
            ->where('due_date', '<', now());
    }

    /**
     * Scope: invoices overdue by more than 30 days.
     */
    public function scopeCriticallyOverdue($query)
    {
        return $query->whereIn('status', ['sent', 'partial'])
            ->where('due_date', '<', now()->subDays(30));
    }
}
