<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $table = 'erp_payslips';

    protected $fillable = [
        'tenant_id',
        'member_id',
        'currency_id',
        'payment_method_id',
        'month',
        'year',
        'worked_days',
        'absent_days',
        'base_amount',
        'net_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'member_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class, 'payslip_id');
    }
}
