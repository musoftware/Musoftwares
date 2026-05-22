<?php

namespace App\Models\Finance;

use App\Helpers\FinanceHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = ['item_title', 'amount', 'qty', 'item_type', 'invoice_id'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timers()
    {
        return $this->hasMany(InvoiceItemTimer::class);
    }

    public function total_str()
    {
        $invoice = $this->invoice()->first();
        $amount = $this->total();
        return FinanceHelper::instance()->format_money($amount, $invoice->currency);
    }

    /** Computed total (no DB column); avoids Eloquent treating total() as a relationship. */
    public function getTotalAttribute(): float
    {
        $invoice = $this->invoice()->first();
        $baseAmount = (double) ($this->amount) + (double) ($this->timers()->sum('amount'));
        $baseTotal = (double) $baseAmount * (double) $this->qty;

        if ($invoice && $invoice->user && $invoice->user->ref_user && $invoice->user->ref_user->shouldAddCommissionToTotal()) {
            $commissionAmount = $invoice->user->ref_user->calculateCommissionAmount($baseTotal, $invoice->currency, $invoice->user);
            $baseTotal += $commissionAmount;
        }

        return (float) $baseTotal;
    }

    public function total(): float
    {
        return $this->getTotalAttribute();
    }

    public function amount_str()
    {
        $invoice = $this->invoice()->first();
        $amount = (double)($this->amount) + (double)($this->timers()->sum('amount'));
        return FinanceHelper::instance()->format_money($amount, $invoice->currency);
    }

    /**
     * Get the base total without commission for this item
     */
    public function base_total()
    {
        $baseAmount = (double)($this->amount) + (double)($this->timers()->sum('amount'));
        return (double)$baseAmount * (double)$this->qty;
    }

    /**
     * Get the commission amount for this item (if applicable)
     */
    public function commission_amount()
    {
        $invoice = $this->invoice()->first();
        if ($invoice && $invoice->user && $invoice->user->ref_user && $invoice->user->ref_user->shouldAddCommissionToTotal()) {
            $baseTotal = $this->base_total();
            return $invoice->user->ref_user->calculateCommissionAmount($baseTotal, $invoice->currency, $invoice->user);
        }
        return 0;
    }

    /**
     * Get formatted commission amount string for this item
     */
    public function commission_amount_str()
    {
        $invoice = $this->invoice()->first();
        return FinanceHelper::instance()->format_money($this->commission_amount(), $invoice->currency);
    }

    /**
     * Get formatted base total string (without commission) for this item
     */
    public function base_total_str()
    {
        $invoice = $this->invoice()->first();
        return FinanceHelper::instance()->format_money($this->base_total(), $invoice->currency);
    }

}
