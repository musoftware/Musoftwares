<?php

namespace Modules\ERP\Models;

use Modules\Core\Models\TenantModel;

class RecurringEntry extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'type', 'description', 'amount', 'currency_code',
        'frequency', 'next_date', 'end_date', 'is_active'
    ];

    protected $casts = [
        'next_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];
}
