<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code', 'name', 'symbol', 'exchange_rate', 'exchange_rate_date', 'is_active'];
    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'exchange_rate_date' => 'date',
        'is_active' => 'boolean',
    ];
}
