<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollContract extends Model
{
    use SoftDeletes;

    protected $table = 'erp_payroll_contracts';

    protected $fillable = [
        'tenant_id',
        'member_id',
        'currency_id',
        'base_salary',
        'payment_frequency',
        'is_active',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'is_active' => 'boolean',
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
}
