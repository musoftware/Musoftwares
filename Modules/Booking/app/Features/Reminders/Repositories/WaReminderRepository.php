<?php

namespace Modules\Booking\app\Features\Reminders\Repositories;

use Modules\Booking\app\Features\Reminders\Models\BookingWaReminder;
use Modules\Booking\app\Features\Reminders\Models\BookingWaTemplate;

class WaReminderRepository
{
    /**
     * Get pending reminders that are due to be sent.
     * Note: Since this is used by background jobs, it should optionally bypass the tenant scope
     * or be run per-tenant in the worker logic. For safety, we explicitly use withoutGlobalScope if querying system-wide.
     */
    public function getDuePendingReminders($limit = 100)
    {
        return BookingWaReminder::withoutGlobalScope('tenant')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->take($limit)
            ->get();
    }

    /**
     * Mark a reminder as sent.
     */
    public function markAsSent(BookingWaReminder $reminder): void
    {
        $reminder->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark a reminder as failed.
     */
    public function markAsFailed(BookingWaReminder $reminder, string $error): void
    {
        $reminder->update([
            'status' => 'failed',
            'error_log' => $error,
        ]);
    }

    /**
     * Get active templates for a tenant by trigger type.
     */
    public function getActiveTemplatesForTrigger(int $tenantId, string $triggerType)
    {
        return BookingWaTemplate::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('trigger_type', $triggerType)
            ->get();
    }
}
