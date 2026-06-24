<?php

namespace Modules\ERP\Models\Approval;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ERP\Models\TenantAwareModel;

class WorkflowDefinition extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_workflow_definitions';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'module_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'workflow_definition_id')->orderBy('order');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'workflow_definition_id');
    }
}
