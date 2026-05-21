<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class GoldPrice extends Model
{
    use HasFactory;

    protected $hidden = [
        'id', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'price_date' => 'datetime',
    ];
}
