<?php

namespace App\Models;

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
