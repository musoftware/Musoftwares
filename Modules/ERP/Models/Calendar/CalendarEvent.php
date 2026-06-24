<?php

namespace Modules\ERP\Models\Calendar;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\ERP\Models\TenantAwareModel;
use App\Models\User;

class CalendarEvent extends TenantAwareModel
{
    use HasUuids;

    protected $table = 'erp_calendar_events';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'is_all_day',
        'location',
        'type',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_all_day' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function meeting(): HasOne
    {
        return $this->hasOne(CalendarMeeting::class, 'event_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(CalendarReminder::class, 'event_id');
    }
}
