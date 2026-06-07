<?php

namespace Modules\Booking\tests\Feature\WaConfirm;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Modules\Booking\Models\Booking;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaConfirmation;
use Modules\Booking\app\Features\WaConfirm\Services\ConfirmationTokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Exception;

class ConfirmationTokenManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = \App\Models\User::factory()->create(['id' => 1]);
        \Illuminate\Support\Facades\DB::table('booking_event_types')->insert(['id' => 1, 'user_id' => $user->id, 'title' => 'test', 'slug' => 'test', 'duration_minutes' => 60]);
    }


    public function test_it_generates_and_validates_tokens_securely()
    {
        $booking = Booking::forceCreate(['status' => 'pending', 'booking_event_type_id' => 1, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $confirmation = BookingWaConfirmation::forceCreate([
            'tenant_id' => 1,
            'booking_id' => $booking->id,
            'status' => 'sent',
            'expires_at' => Carbon::now()->addDays(2),
        ]);

        $manager = new ConfirmationTokenManager();
        
        $tokenModel = $manager->generateActionToken($confirmation, 'confirm', 24);
        
        // Assert the raw token is returned but the DB only stores the hash
        $this->assertNotNull($tokenModel->raw_token);
        $this->assertNotEquals($tokenModel->raw_token, $tokenModel->token_hash);
        $this->assertEquals(hash('sha256', $tokenModel->raw_token), $tokenModel->token_hash);

        // Validate
        $validatedToken = $manager->validateAndConsumeToken($tokenModel->raw_token);
        $this->assertEquals('confirm', $validatedToken->action_type);
        $this->assertTrue($validatedToken->isUsed());

        // Re-use should fail
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('This action has already been performed.');
        $manager->validateAndConsumeToken($tokenModel->raw_token);
    }

    public function test_expired_tokens_throw_exception()
    {
        $booking = Booking::forceCreate(['status' => 'pending', 'booking_event_type_id' => 1, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $confirmation = BookingWaConfirmation::forceCreate([
            'tenant_id' => 1,
            'booking_id' => $booking->id,
            'status' => 'sent',
            'expires_at' => Carbon::now()->addDays(2),
        ]);

        $manager = new ConfirmationTokenManager();
        $tokenModel = $manager->generateActionToken($confirmation, 'cancel', -1); // Expired immediately

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('This confirmation link has expired.');
        $manager->validateAndConsumeToken($tokenModel->raw_token);
    }
}
