<?php

namespace Modules\Booking\app\Features\Reminders\Services;

use Modules\Booking\app\Features\Reminders\Models\BookingWaReminder;
use Modules\Booking\app\Features\Reminders\Models\BookingWaTemplate;
use Modules\Booking\app\Features\Reminders\Repositories\WaReminderRepository;
use Modules\Booking\app\Features\Reminders\Events\BookingWaReminderScheduled;
use Illuminate\Support\Str;

class BookingWaReminderService
{
    protected $repository;
    protected $limitsService;

    public function __construct(WaReminderRepository $repository, WaReminderLimitsService $limitsService)
    {
        $this->repository = $repository;
        $this->limitsService = $limitsService;
    }

    /**
     * Parse variables in the template body.
     */
    public function compileMessage(string $body, $booking): string
    {
        // Simple string replacement for demonstration.
        // Assuming $booking has relationships like customer, service.
        $replacements = [
            '{{customer_name}}' => $booking->customer->name ?? 'Customer',
            '{{service_name}}' => $booking->service->name ?? 'Service',
            '{{time}}' => $booking->booking_date ? $booking->booking_date->format('g:i A') : 'TBD',
            '{{date}}' => $booking->booking_date ? $booking->booking_date->format('Y-m-d') : 'TBD',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $body);
    }

    /**
     * Schedule a reminder for a specific booking using a template.
     */
    public function scheduleReminder($booking, BookingWaTemplate $template, \Carbon\Carbon $scheduledAt)
    {
        // Must check if feature is enabled, though often listeners check this too.
        if (!$this->limitsService->canUse($booking->tenant_id)) {
            return null; // Usage limit reached or feature not unlocked
        }

        // Needs a customer phone
        $phone = $booking->customer->phone ?? null;
        if (!$phone) {
            return null;
        }

        $message = $this->compileMessage($template->body, $booking);

        $reminder = BookingWaReminder::create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'template_id' => $template->id,
            'scheduled_at' => $scheduledAt,
            'phone' => $phone,
            'message' => $message,
            'status' => 'pending',
        ]);

        event(new BookingWaReminderScheduled($reminder));

        return $reminder;
    }

    /**
     * Schedule all matching reminders for a newly created or confirmed booking.
     */
    public function scheduleRemindersForBookingEvent($booking, string $triggerType)
    {
        $templates = $this->repository->getActiveTemplatesForTrigger($booking->tenant_id, $triggerType);

        foreach ($templates as $template) {
            $scheduledAt = $this->calculateScheduleTime($booking, $template);
            $this->scheduleReminder($booking, $template, $scheduledAt);
        }
    }

    protected function calculateScheduleTime($booking, BookingWaTemplate $template): \Carbon\Carbon
    {
        // For 'on_booking_confirmed', we schedule it immediately (now).
        // For 'before_1_hour', we schedule it 1 hour before the booking_date.
        if ($template->trigger_type === 'on_booking_confirmed') {
            return now();
        }

        if (Str::startsWith($template->trigger_type, 'before_')) {
            // e.g., 'before_24_hours'
            $parts = explode('_', $template->trigger_type);
            $amount = (int) ($parts[1] ?? 1);
            $unit = $parts[2] ?? 'hours';
            
            if ($booking->booking_date) {
                $time = clone $booking->booking_date;
                return $time->sub($unit, $amount);
            }
        }

        return now();
    }
}
