<?php

namespace Modules\ERP\Models\Approval;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_workflow_steps';

    protected $fillable = [
        'workflow_definition_id',
        'name',
        'order',
        'approver_type',
        'approver_id',
        'requires_all',
    ];

    protected $casts = [
        'requires_all' => 'boolean',
        'order' => 'integer',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }
}
