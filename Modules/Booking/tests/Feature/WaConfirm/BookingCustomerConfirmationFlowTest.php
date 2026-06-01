<?php

namespace Modules\Booking\tests\Feature\WaConfirm;

use Tests\TestCase;
use Modules\Booking\Models\Booking;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaConfirmation;
use Modules\Booking\app\Features\WaConfirm\Services\ConfirmationTokenManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BookingCustomerConfirmationFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_click_link_to_confirm_appointment()
    {
        $booking = Booking::forceCreate(['tenant_id' => 1, 'status' => 'pending']);
        $confirmation = BookingWaConfirmation::forceCreate([
            'tenant_id' => 1,
            'booking_id' => $booking->id,
            'status' => 'sent',
            'expires_at' => now()->addDays(2),
        ]);

        $tokenManager = new ConfirmationTokenManager();
        $tokenModel = $tokenManager->generateActionToken($confirmation, 'confirm');

        // Customer clicks the secure WhatsApp link (Mocking the HTTP request without middleware for simplicity here)
        $controller = new \Modules\Booking\app\Features\WaConfirm\Http\Controllers\WaConfirmationPublicController(
            $tokenManager,
            new \Modules\Booking\app\Features\WaConfirm\Services\WhatsAppConfirmationActionProcessor()
        );

        $response = $controller->handleAction($tokenModel->raw_token);
        
        $this->assertTrue($response->getData()->success);
        $this->assertEquals('confirmed', $booking->fresh()->status);
        $this->assertTrue($tokenModel->fresh()->isUsed());
    }

    public function test_spoofed_or_invalid_tokens_are_rejected()
    {
        $controller = app(\Modules\Booking\app\Features\WaConfirm\Http\Controllers\WaConfirmationPublicController::class);

        $response = $controller->handleAction('invalid_fake_token_string');

        $this->assertFalse($response->getData()->success);
        $this->assertEquals('Invalid or spoofed action token.', $response->getData()->error);
    }
}
