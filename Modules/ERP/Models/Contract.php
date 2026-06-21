<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends TenantModel
{
    use SoftDeletes;

    protected $table = 'erp_contracts';

    protected $fillable = [
        'tenant_id',
        'title',
        'client_id',
        'value',
        'currency_id',
        'status',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }
}
