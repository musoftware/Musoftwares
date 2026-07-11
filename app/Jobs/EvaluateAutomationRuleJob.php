<?php

namespace App\Jobs;

use App\Models\AutomationRule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EvaluateAutomationRuleJob implements ShouldQueue
{
    use Queueable;

    public $rule;

    public $eventData;

    /**
     * Create a new job instance.
     */
    public function __construct(AutomationRule $rule, array $eventData)
    {
        $this->rule = $rule;
        $this->eventData = $eventData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $conditionsMet = true;

        if ($this->rule->conditions) {
            foreach ($this->rule->conditions as $key => $expectedValue) {
                if (! isset($this->eventData[$key]) || $this->eventData[$key] != $expectedValue) {
                    $conditionsMet = false;
                    break;
                }
            }
        }

        if ($conditionsMet && $this->rule->actions) {
            foreach ($this->rule->actions as $action) {
                ExecuteAutomationActionJob::dispatch($this->rule, $action, $this->eventData);
            }
        }
    }
}
