<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class Project extends TenantModel
{
    protected $table = 'erp_projects';

    protected $fillable = [
        'tenant_id', 'client_id', 'name', 'description', 'status',
        'budget', 'currency', 'currency_id', 'due_date', 'completed_at', 'created_by'
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'budget' => 'decimal:2',
        'currency_id' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'project_id');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }
}
