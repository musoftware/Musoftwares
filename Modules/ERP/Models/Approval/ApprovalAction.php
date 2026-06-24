<?php

namespace Modules\ERP\Models\Approval;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ApprovalAction extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'erp_approval_actions';

    protected $fillable = [
        'approval_request_id',
        'workflow_step_id',
        'approver_id',
        'action',
        'comments',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
