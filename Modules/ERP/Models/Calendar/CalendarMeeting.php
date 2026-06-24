<?php

namespace Modules\ERP\Models\Calendar;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantAwareModel;
use App\Models\User;

class CalendarMeeting extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_calendar_meetings';

    protected $fillable = [
        'tenant_id',
        'event_id',
        'organizer_id',
        'meeting_url',
        'status',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'event_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
}
