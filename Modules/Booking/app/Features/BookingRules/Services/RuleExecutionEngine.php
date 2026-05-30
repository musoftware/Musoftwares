<?php

namespace Modules\Booking\app\Features\BookingRules\Services;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRule;
use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRuleExecution;
use Modules\Booking\app\Features\BookingRules\Services\Evaluators\RuleConditionEvaluator;
use Modules\Booking\app\Features\BookingRules\Services\Processors\RuleActionProcessor;

class RuleExecutionEngine
{
    protected BookingAdvancedRulesService $rulesService;
    protected BookingRulesAuditService $auditService;
    protected RuleConditionEvaluator $evaluator;
    protected RuleActionProcessor $processor;

    public function __construct(
        BookingAdvancedRulesService $rulesService,
        BookingRulesAuditService $auditService,
        RuleConditionEvaluator $evaluator,
        RuleActionProcessor $processor
    ) {
        $this->rulesService = $rulesService;
        $this->auditService = $auditService;
        $this->evaluator = $evaluator;
        $this->processor = $processor;
    }

    public function executeForEvent(int $tenantId, string $eventTrigger, array $payload, ?int $bookingId = null): void
    {
        $rules = $this->rulesService->getActiveRulesForEvent($tenantId, $eventTrigger);

        foreach ($rules as $rule) {
            $execution = $this->rulesService->createExecutionRecord($tenantId, $rule->id, $bookingId);
            $startTime = microtime(true);

            try {
                if ($this->evaluator->evaluate($rule, $payload)) {
                    $this->processor->process($rule, $payload, $execution);
                    $execution->update(['status' => 'success']);
                } else {
                    $execution->update(['status' => 'skipped']);
                }
            } catch (\Exception $e) {
                $execution->update(['status' => 'failed']);
                $this->auditService->logError($execution->id, $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }

            $execution->update([
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000)
            ]);
        }
    }
}
