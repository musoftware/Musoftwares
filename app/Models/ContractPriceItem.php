<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContractPriceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'default_price',
        'currency_id',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
    ];
}
