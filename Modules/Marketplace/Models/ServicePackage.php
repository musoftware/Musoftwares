<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePackage extends Model
{
    protected $table = 'marketplace_packages';

    protected $fillable = [
        'service_id',
        'name',
        'description',
        'price',
        'currency_code',
        'delivery_days',
        'revisions',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
