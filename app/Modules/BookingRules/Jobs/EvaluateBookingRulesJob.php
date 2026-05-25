<?php

namespace App\Modules\BookingRules\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\BookingRules\Services\RuleExecutionEngine;

class EvaluateBookingRulesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tenantId;
    public string $eventTrigger;
    public array $payload;
    public ?int $bookingId;

    public function __construct(int $tenantId, string $eventTrigger, array $payload, ?int $bookingId = null)
    {
        $this->tenantId = $tenantId;
        $this->eventTrigger = $eventTrigger;
        $this->payload = $payload;
        $this->bookingId = $bookingId;
    }

    public function handle(RuleExecutionEngine $engine): void
    {
        $engine->executeForEvent($this->tenantId, $this->eventTrigger, $this->payload, $this->bookingId);
    }
}
