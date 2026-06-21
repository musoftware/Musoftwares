<?php

namespace Modules\Marketplace\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ServiceDiscount extends Model
{
    use SoftDeletes;

    protected $table = 'marketplace_service_discounts';

    protected $fillable = [
        'service_id', 'code', 'percentage', 'max_uses', 'used_count',
        'starts_at', 'expires_at', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
