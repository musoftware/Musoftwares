<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'changed_by',
        'action',
        'old_value',
        'new_value',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get Cairo formatted created_at string
     */
    public function getCreatedAtCairoAttribute(): string
    {
        return $this->created_at 
            ? Carbon::parse($this->created_at)->setTimezone('Africa/Cairo')->format('Y-m-d H:i') 
            : '-';
    }
}
