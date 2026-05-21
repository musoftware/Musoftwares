<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * PlatformInvoice — Real money invoice from Musoftware (admin) to a platform user.
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  PLATFORM BILLING — NOT ERP                                      ║
 * ║                                                                  ║
 * ║  These invoices represent charges from the platform (admin)      ║
 * ║  to a subscribed user. This is REAL money (e.g. subscription     ║
 * ║  fees, service charges, manual charges).                         ║
 * ║                                                                  ║
 * ║  DO NOT confuse with Modules\Core\Models\Invoice which is the     ║
 * ║  tool used by platform users to bill their own business clients. ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */
class PlatformInvoice extends Model
{
    protected $table = 'platform_invoices';

    protected $fillable = [
        'uuid',
        'user_id',
        'invoice_number',
        'title',
        'description',
        'amount',
        'paid_amount',
        'currency',
        'status',
        'due_date',
        'issued_at',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date'    => 'date',
        'issued_at'   => 'date',
        'paid_at'     => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->invoice_number)) {
                $model->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $last = self::whereYear('created_at', $year)->count();
        return 'MINV-' . $year . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getRemainingAttribute(): float
    {
        return round((float) $this->amount - (float) $this->paid_amount, 2);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlatformInvoiceItem::class, 'invoice_id');
    }

    /**
     * Mark the invoice as paid manually.
     */
    public function markAsPaid()
    {
        if ($this->status === 'paid') {
            return;
        }

        $this->paid_amount = $this->amount;
        $this->status = 'paid';
        $this->paid_at = now();
        $this->save();

        // Create received and used transactions to record the direct payment
        PlatformTransaction::addBalance(
            $this->user_id,
            $this->amount,
            'Payment received for Platform Invoice #' . $this->invoice_number,
            'received',
            $this->currency,
            $this->id
        );
        PlatformTransaction::addBalance(
            $this->user_id,
            -1 * $this->amount,
            'Payment applied to Platform Invoice #' . $this->invoice_number,
            'used',
            $this->currency,
            $this->id
        );
    }

    /**
     * Bill the invoice (pay it using the user's wallet balance).
     */
    public function billInvoice()
    {
        if ($this->status === 'paid') {
            return;
        }

        $unpaid = $this->remaining;
        if ($unpaid <= 0) {
            return;
        }

        // Deduct from wallet balance
        PlatformTransaction::addBalance(
            $this->user_id,
            -1 * $unpaid,
            'Paid Platform Invoice #' . $this->invoice_number,
            'used',
            $this->currency,
            $this->id
        );

        $this->paid_amount = $this->amount;
        $this->status = 'paid';
        $this->paid_at = now();
        $this->save();
    }

    /**
     * Partially bill the invoice using wallet balance.
     */
    public function partiallyBillInvoice($paidAmount)
    {
        if ($this->status === 'paid' || $paidAmount <= 0) {
            return;
        }

        $remaining = $this->remaining;
        $actualPaid = min($paidAmount, $remaining);

        // Deduct from wallet balance
        PlatformTransaction::addBalance(
            $this->user_id,
            -1 * $actualPaid,
            'Partially Paid Platform Invoice #' . $this->invoice_number,
            'used',
            $this->currency,
            $this->id
        );

        $this->paid_amount += $actualPaid;
        if ($this->remaining <= 0) {
            $this->status = 'paid';
            $this->paid_at = now();
        } else {
            $this->status = 'partially_paid';
        }
        $this->save();
    }

    /**
     * Cancel the invoice and refund any paid amounts back to the user's wallet.
     */
    public function cancelInvoice()
    {
        if (in_array($this->status, ['paid', 'partially_paid']) && $this->paid_amount > 0) {
            PlatformTransaction::addBalance(
                $this->user_id,
                $this->paid_amount,
                'Refund for Cancelled Platform Invoice #' . $this->invoice_number,
                'refunded',
                $this->currency,
                $this->id
            );
            
            $this->paid_amount = 0;
        }
        
        $this->status = 'cancelled';
        $this->save();
    }
}
