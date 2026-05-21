<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'name',
        'title',
        'description',
        'price',
        'delivery_days',
        'revisions',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Whether the provider has supplied features for this package.
     */
    public function hasFeatures(): bool
    {
        return !empty($this->features) && is_array($this->features);
    }

    /**
     * Get formatted revisions display string.
     * -1 = Unlimited, 0 = None, else the number.
     */
    public function getFormattedRevisionsAttribute(): string
    {
        if ($this->revisions === -1 || $this->revisions === '-1') {
            return __('Unlimited');
        }
        return (string) $this->revisions;
    }

    /**
     * Get formatted delivery days display string.
     */
    public function getFormattedDeliveryAttribute(): string
    {
        $days = (int) $this->delivery_days;
        if ($days === 1) {
            return '1 ' . __('Day Delivery');
        }
        return $days . ' ' . __('Days Delivery');
    }
}
