<?php

namespace Modules\Booking\app\Features\WaConfirm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaConfirmation;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaLog;

class SendBookingConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $confirmation;
    public $messageBody;

    // Retry settings for API rate limits
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 mins, 15 mins

    public function __construct(BookingWaConfirmation $confirmation, string $messageBody)
    {
        $this->confirmation = $confirmation;
        $this->messageBody = $messageBody;
    }

    public function handle(): void
    {
        // 1. Double check the confirmation hasn't been cancelled or already sent
        if ($this->confirmation->status !== 'pending') {
            return;
        }

        // 2. Here we would integrate with the actual WhatsApp Provider API (Twilio / Meta)
        // Http::post('https://graph.facebook.com/v17.0/PHONE_NUMBER_ID/messages', [...]);

        // 3. Mark as sent
        $this->confirmation->status = 'sent';
        $this->confirmation->sent_at = now();
        $this->confirmation->save();

        // 4. Log the success
        BookingWaLog::create([
            'tenant_id' => $this->confirmation->tenant_id,
            'confirmation_id' => $this->confirmation->id,
            'event_type' => 'whatsapp_dispatched',
            'payload' => ['provider' => 'mock_provider_for_now'],
        ]);

        // 5. Fire Event
        event(new \Modules\Booking\app\Features\WaConfirm\Events\BookingWhatsAppConfirmationSent($this->confirmation));
    }

    public function failed(\Throwable $exception)
    {
        $this->confirmation->status = 'failed';
        $this->confirmation->save();

        BookingWaLog::create([
            'tenant_id' => $this->confirmation->tenant_id,
            'confirmation_id' => $this->confirmation->id,
            'event_type' => 'whatsapp_dispatch_failed',
            'payload' => ['error' => $exception->getMessage()],
        ]);
        
        event(new \Modules\Booking\app\Features\WaConfirm\Events\BookingWhatsAppConfirmationFailed($this->confirmation, $exception->getMessage()));
    }
}
