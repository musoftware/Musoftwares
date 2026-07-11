<?php

namespace Tests\Unit\BookingRules;

use Modules\Booking\app\Features\BookingRules\Models\BookingAdvancedRule;
use Modules\Booking\app\Features\BookingRules\Services\Evaluators\RuleConditionEvaluator;
use PHPUnit\Framework\TestCase;

class RuleConditionEvaluatorTest extends TestCase
{
    public function test_it_returns_true_when_no_conditions_exist()
    {
        $evaluator = new RuleConditionEvaluator;
        $rule = $this->createMock(BookingAdvancedRule::class);
        $rule->method('__get')->with('conditions')->willReturn(collect([]));

        $this->assertTrue($evaluator->evaluate($rule, []));
    }

    // Add more tests for specific conditions...
}
