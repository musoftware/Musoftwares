<?php

namespace Modules\Booking\app\Features\WaReminders\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\WaReminders\Models\WaSchedule;
use Modules\Booking\app\Features\WaReminders\Models\WaTemplate;
use Modules\Booking\app\Features\WaReminders\Models\WaLog;
use Modules\Booking\app\Features\WaReminders\Services\WhatsAppProviderInterface;
use Modules\Booking\app\Features\WaReminders\Services\WhatsAppTemplateRenderer;
use Modules\Booking\app\Features\WaReminders\Services\BookingWhatsAppLimitsService;
use Modules\Booking\app\Features\WaReminders\Events\BookingReminderSent;
use Modules\Booking\app\Features\WaReminders\Events\BookingReminderFailed;
use Modules\Booking\app\Features\WaReminders\Notifications\WhatsAppDeliveryFailedNotification;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $schedule;
    public $tries = 3;
    public $backoff = 60; // Wait 60s before retrying

    public function __construct(WaSchedule $schedule)
    {
        $this->schedule = $schedule;
    }

    public function handle(
        WhatsAppProviderInterface $provider,
        WhatsAppTemplateRenderer $renderer,
        BookingWhatsAppLimitsService $limitsService
    ): void {
        // Double check it's still pending
        if ($this->schedule->status !== 'pending') {
            return;
        }

        $tenantId = $this->schedule->tenant_id;

        // Check SaaS limits
        if (!$limitsService->canSend($tenantId)) {
            $this->markFailed('Tenant exceeded WhatsApp quota or feature locked.');
            return;
        }

        $booking = $this->schedule->booking;
        if (!$booking || !$booking->customer) {
            $this->markFailed('Missing booking or customer data.');
            return;
        }

        // Fetch template
        $template = WaTemplate::where('tenant_id', $tenantId)
            ->where('type', $this->schedule->trigger_type)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            $this->markFailed('No active template found for trigger: ' . $this->schedule->trigger_type);
            return;
        }

        // Render message
        $message = $renderer->render($template->content, $booking);
        $phone = $booking->customer->phone;

        try {
            // Send
            $providerMessageId = $provider->sendMessage($phone, $message);
            $limitsService->increaseUsage($tenantId);

            // Log it
            $log = WaLog::create([
                'tenant_id' => $tenantId,
                'booking_id' => $booking->id,
                'phone_number' => $phone,
                'message_content' => $message,
                'provider_message_id' => $providerMessageId,
                'delivery_status' => 'sent',
            ]);

            // Mark schedule as sent
            $this->schedule->update(['status' => 'sent']);

            // Dispatch Event
            event(new BookingReminderSent($log));

        } catch (\Exception $e) {
            if ($this->attempts() >= $this->tries) {
                $this->markFailed($e->getMessage());
            }
            throw $e; // Trigger retry
        }
    }

    protected function markFailed(string $reason)
    {
        $this->schedule->update(['status' => 'failed']);
        
        $log = WaLog::create([
            'tenant_id' => $this->schedule->tenant_id,
            'booking_id' => $this->schedule->booking_id,
            'phone_number' => $this->schedule->booking->customer->phone ?? 'unknown',
            'message_content' => '',
            'delivery_status' => 'failed',
            'error_reason' => $reason,
        ]);

        event(new BookingReminderFailed($log));

        // Let the tenant admin know
        // Normally we'd fetch the tenant admin, but here's a conceptual call:
        // $adminUser = ...
        // $adminUser->notify(new WhatsAppDeliveryFailedNotification($log));
    }
}
