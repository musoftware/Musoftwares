<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'serial',
        'status',
        'order_id',
        'sold_at',
        'expires_at',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function order()
    {
        return $this->belongsTo(ServiceOrder::class, 'order_id');
    }
}
