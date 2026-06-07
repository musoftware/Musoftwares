<?php

namespace Modules\Booking\tests\Feature\WaConfirm;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaConfirmation;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaActionToken;
use Modules\Booking\app\Features\WaConfirm\Services\WhatsAppConfirmationActionProcessor;
use Modules\Booking\app\Features\WaConfirm\Events\BookingConfirmedByCustomer;
use Modules\Booking\app\Features\WaConfirm\Events\BookingCancelledByCustomer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;

class WhatsAppConfirmationActionProcessorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::disableForeignKeyConstraints();
    }


    public function test_it_confirms_booking_and_fires_event()
    {
        Event::fake();

        $booking = Booking::forceCreate(['status' => 'pending', 'booking_event_type_id' => 1, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $confirmation = BookingWaConfirmation::forceCreate([
            'tenant_id' => 1,
            'booking_id' => $booking->id,
            'status' => 'sent',
            'expires_at' => now()->addDays(2),
        ]);
        
        $token = BookingWaActionToken::forceCreate([
            'tenant_id' => 1,
            'confirmation_id' => $confirmation->id,
            'token_hash' => 'dummy_hash',
            'action_type' => 'confirm',
            'expires_at' => now()->addDays(1),
        ]);

        $processor = new WhatsAppConfirmationActionProcessor();
        $processor->process($token);

        $this->assertEquals('confirmed', $booking->fresh()->status);
        $this->assertEquals('read', $confirmation->fresh()->status);

        Event::assertDispatched(BookingConfirmedByCustomer::class, function ($e) use ($booking) {
            return $e->booking->id === $booking->id;
        });
    }

    public function test_it_cancels_booking_and_fires_event()
    {
        Event::fake();

        $booking = Booking::forceCreate(['status' => 'pending', 'booking_event_type_id' => 1, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $confirmation = BookingWaConfirmation::forceCreate([
            'tenant_id' => 1,
            'booking_id' => $booking->id,
            'status' => 'sent',
            'expires_at' => now()->addDays(2),
        ]);
        
        $token = BookingWaActionToken::forceCreate([
            'tenant_id' => 1,
            'confirmation_id' => $confirmation->id,
            'token_hash' => 'dummy_hash',
            'action_type' => 'cancel',
            'expires_at' => now()->addDays(1),
        ]);

        $processor = new WhatsAppConfirmationActionProcessor();
        $processor->process($token);

        $this->assertEquals('cancelled', $booking->fresh()->status);

        Event::assertDispatched(BookingCancelledByCustomer::class);
    }
}
