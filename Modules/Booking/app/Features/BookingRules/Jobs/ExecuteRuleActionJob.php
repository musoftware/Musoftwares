<?php

namespace Modules\Booking\app\Features\BookingRules\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteRuleActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $actionId;
    public array $payload;
    public int $executionId;

    public function __construct(int $actionId, array $payload, int $executionId)
    {
        $this->actionId = $actionId;
        $this->payload = $payload;
        $this->executionId = $executionId;
    }

    public function handle(): void
    {
        // Resolve action and process via RuleActionProcessor
    }
}
