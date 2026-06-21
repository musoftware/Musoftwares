<?php

namespace Modules\Marketplace\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ServiceExtra extends Model
{
    use SoftDeletes;

    protected $table = 'marketplace_service_extras';

    protected $fillable = [
        'service_id', 'title', 'price', 'duration_days'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
