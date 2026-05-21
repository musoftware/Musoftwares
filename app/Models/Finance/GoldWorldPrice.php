<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GoldWorldPrice extends Model
{
    use HasFactory;

    protected $casts = [
        'price_date' => 'datetime',
    ];
}
