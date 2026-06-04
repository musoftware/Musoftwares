<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ServiceSerial extends Model
{
    protected $table = 'marketplace_service_serials';

    protected $fillable = [
        'service_id', 'serial_code', 'is_used', 'used_by', 'used_at'
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
