<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Expense extends TenantModel
{
    protected $table = 'erp_expenses';

    protected $fillable = [
        'tenant_id', 'title', 'amount', 'category', 'date', 'description', 'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
