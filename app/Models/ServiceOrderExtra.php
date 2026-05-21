<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderExtra extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_order_id',
        'service_extra_id',
        'title',
        'price',
        'duration_days'
    ];

    public function order()
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    public function extra()
    {
        return $this->belongsTo(ServiceExtra::class, 'service_extra_id');
    }
}
