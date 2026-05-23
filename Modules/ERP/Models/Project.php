<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Ticket;

class Project extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'description', 'status',
        'budget', 'currency', 'due_date', 'completed_at', 'created_by'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'budget' => 'decimal:2',
    ];

    public function platformClient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function tenantClient(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function getClientAttribute()
    {
        return $this->tenant_id === Tenant::platformId() 
            ? $this->platformClient 
            : $this->tenantClient;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'project_id');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }
}
