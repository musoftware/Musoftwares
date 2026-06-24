<?php

namespace Modules\ERP\Models\Approval;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Escalation extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_escalations';

    protected $fillable = [
        'approval_request_id',
        'workflow_step_id',
        'escalated_to_id',
        'escalated_to_type',
        'escalated_at',
        'reason',
        'is_resolved',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'escalated_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }
}
