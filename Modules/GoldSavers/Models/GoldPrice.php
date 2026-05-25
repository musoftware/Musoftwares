<?php

namespace Modules\GoldSavers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'karat_24',
        'karat_21',
        'karat_18',
        'currency',
        'global_price_usd',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
