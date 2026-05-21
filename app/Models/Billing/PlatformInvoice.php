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
 * ║  DO NOT confuse with Modules\ERP\Models\Invoice which is the     ║
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
}
