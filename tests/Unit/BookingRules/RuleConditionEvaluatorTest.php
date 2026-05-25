<?php

namespace Tests\Unit\BookingRules;

use PHPUnit\Framework\TestCase;
use App\Modules\BookingRules\Services\Evaluators\RuleConditionEvaluator;
use App\Modules\BookingRules\Models\BookingAdvancedRule;

class RuleConditionEvaluatorTest extends TestCase
{
    public function test_it_returns_true_when_no_conditions_exist()
    {
        $evaluator = new RuleConditionEvaluator();
        $rule = $this->createMock(BookingAdvancedRule::class);
        $rule->method('__get')->with('conditions')->willReturn(collect([]));

        $this->assertTrue($evaluator->evaluate($rule, []));
    }

    // Add more tests for specific conditions...
}
