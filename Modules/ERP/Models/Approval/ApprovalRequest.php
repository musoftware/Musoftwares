<?php

namespace Modules\ERP\Models\Approval;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\ERP\Models\TenantAwareModel;
use App\Models\User;

class ApprovalRequest extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_approval_requests';

    protected $fillable = [
        'tenant_id',
        'workflow_definition_id',
        'approvable_type',
        'approvable_id',
        'status',
        'current_step_order',
        'requester_id',
    ];

    protected $casts = [
        'current_step_order' => 'integer',
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'approval_request_id');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(Escalation::class, 'approval_request_id');
    }
}
