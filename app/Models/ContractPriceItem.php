<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractPriceItem extends Model
{
    use HasFactory;

    protected $table = 'contract_price_items';

    protected $fillable = [
        'name',
        'description',
        'default_price',
        'currency_id',
        'key',
        'name_ar',
        'name_en',
        'standalone_hours',
        'marginal_hours',
        'complexity',
        'keywords',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_price'    => 'decimal:2',
        'standalone_hours' => 'integer',
        'marginal_hours'   => 'integer',
        'keywords'         => 'array',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
    ];
}
