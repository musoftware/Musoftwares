<?php

namespace Modules\Booking\tests\Feature\QueueManagement;

use Tests\TestCase;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueue;
use Modules\Booking\app\Features\QueueManagement\Services\QueueTokenGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class QueueTokenGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_sequential_tokens_with_prefix()
    {
        $queue = BookingQueue::forceCreate([
            'tenant_id' => 1,
            'name' => 'Walk-in Clinic',
            'prefix' => 'CL',
        ]);

        $generator = new QueueTokenGenerator();

        $token1 = $generator->generateNextToken($queue);
        $this->assertEquals('CL-001', $token1['token_number']);
        $this->assertEquals(1, $token1['sequence_number']);

        $token2 = $generator->generateNextToken($queue);
        $this->assertEquals('CL-002', $token2['token_number']);
        $this->assertEquals(2, $token2['sequence_number']);
    }

    public function test_it_resets_sequence_at_midnight()
    {
        $queue = BookingQueue::forceCreate([
            'tenant_id' => 1,
            'name' => 'Walk-in Clinic',
            'prefix' => 'A',
            'current_sequence_date' => Carbon::yesterday(),
            'current_sequence_number' => 55,
        ]);

        $generator = new QueueTokenGenerator();

        $token = $generator->generateNextToken($queue);
        $this->assertEquals('A-001', $token['token_number']);
        $this->assertEquals(1, $token['sequence_number']);
    }
}
