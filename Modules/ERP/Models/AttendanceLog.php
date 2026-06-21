<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use SoftDeletes;

    protected $table = 'erp_attendance_logs';

    protected $fillable = [
        'tenant_id',
        'member_id',
        'date',
        'clock_in_at',
        'clock_out_at',
        'total_minutes',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'total_minutes' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'member_id');
    }
}
