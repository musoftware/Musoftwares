<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User;

class Activity extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'client_id', 'subject_type', 'subject_id', 
        'action', 'description', 'causer_id', 'properties'
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
