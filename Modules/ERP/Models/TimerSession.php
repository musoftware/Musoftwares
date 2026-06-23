<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimerSession extends Model
{
    use SoftDeletes;

    protected $table = 'erp_timer_sessions';

    protected $fillable = [
        'invoice_item_id', 'started_at', 'stopped_at', 'duration_seconds',
        'started_by', 'stopped_by', 'note'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\ERP\Models\TeamMember::class, 'started_by');
    }

    public function stoppedBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\ERP\Models\TeamMember::class, 'stopped_by');
    }
}
