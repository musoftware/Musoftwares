<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use SoftDeletes;

    protected $table = 'erp_leave_requests';

    protected $fillable = [
        'tenant_id',
        'member_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'admin_response',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'member_id');
    }
}
