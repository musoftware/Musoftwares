<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class SupportTicket extends TenantModel
{
    protected $table = 'erp_support_tickets';

    protected $fillable = [
        'tenant_id', 'client_id', 'project_id', 'subject', 'description',
        'status', 'priority', 'assigned_to', 'created_by'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }
}
