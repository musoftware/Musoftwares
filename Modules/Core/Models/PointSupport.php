<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointSupport extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'business_amount',
        'currency',
        'business_calculated',
    ];
}
