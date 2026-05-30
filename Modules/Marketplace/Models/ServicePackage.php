<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePackage extends Model
{
    use SoftDeletes;
    protected $table = 'marketplace_packages';

    protected $fillable = [
        'service_id',
        'name',
        'description',
        'price',
        'currency_id',
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
