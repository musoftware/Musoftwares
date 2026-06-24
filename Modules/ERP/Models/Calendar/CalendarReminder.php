<?php

namespace Modules\ERP\Models\Calendar;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;

class CalendarReminder extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_calendar_reminders';

    protected $fillable = [
        'tenant_id',
        'event_id',
        'remind_at',
        'method',
        'is_sent',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }
}
