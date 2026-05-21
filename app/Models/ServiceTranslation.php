<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTranslation extends Model
{
    protected $fillable = [
        'service_id',
        'locale',
        'field',
        'value',
    ];

    /**
     * Get the service that owns the translation
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
