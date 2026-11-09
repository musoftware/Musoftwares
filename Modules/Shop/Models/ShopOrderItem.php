<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'product_name', 'size', 'color', 'unit_price', 'quantity', 'line_total', 'angazny_stock_id', 'angazny_price_effect'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'integer',
        'angazny_stock_id' => 'integer',
        'angazny_price_effect' => 'decimal:2',
    ];
}
