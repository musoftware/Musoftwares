<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Services\AI\AiContextManager;
use App\Services\AI\AiResponseValidator;
use App\Services\AI\ConversationStateMachine;
use App\Services\AI\RequirementsAnalyzer;
use App\Services\AI\ScopePricingEngine;
use Tests\TestCase;

class AiAgencySystemOverhaulTest extends TestCase
{
    protected AiResponseValidator   $validator;
    protected ConversationStateMachine $stateMachine;
    protected RequirementsAnalyzer  $requirementsAnalyzer;
    protected ScopePricingEngine    $pricingEngine;
    protected AiContextManager      $contextManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator            = new AiResponseValidator();
        $this->stateMachine         = new ConversationStateMachine();
        $this->requirementsAnalyzer = new RequirementsAnalyzer();
        $this->pricingEngine        = new ScopePricingEngine();
        $this->contextManager       = new AiContextManager();
    }

    public function test_ai_response_validator_repairs_json_and_applies_defaults()
    {
        $rawWithFences = "```json\n{\n  \"reply\": \"أهلاً بك\",\n  \"intent\": {\"primary\": \"greeting\"}\n}\n```";
        $result = $this->validator->validateAndRepair($rawWithFences);

        $this->assertIsArray($result);
        $this->assertEquals('greeting', $result['intent']['primary']);
        $this->assertArrayHasKey('reasoning', $result);
        $this->assertArrayHasKey('requirements_analysis', $result);
        $this->assertArrayHasKey('action_proposals', $result);
    }

    public function test_conversation_state_machine_stage_transition_guards()
    {
        $project = new Project(['ai_context' => ['current_stage' => 'GREETING']]);

        // Cannot jump directly to EXECUTION without invoice
        $this->assertFalse($this->stateMachine->canTransition($project, 'EXECUTION', ['completeness_score' => 10]));

        // Can transition to DISCOVERY
        $this->assertTrue($this->stateMachine->canTransition($project, 'DISCOVERY', ['completeness_score' => 20]));

        // Can transition to VALUATION when completeness score >= 60
        $this->assertTrue($this->stateMachine->canTransition($project, 'VALUATION', ['completeness_score' => 65]));
    }

    public function test_requirements_analyzer_detects_completeness_and_missing_info()
    {
        $project = new Project([
            'project_name' => 'متجر إلكتروني للملابس',
            'ai_context'   => [
                'pending_features' => ['سلة مشتريات', 'بوابة دفع سترايب'],
            ],
        ]);

        $analysis = $this->requirementsAnalyzer->analyze($project, 'عايز اعمل متجر لبيع الملابس أونلاين وتوصيل في مصر والسعودية مع دفع فيزا');

        $this->assertGreaterThanOrEqual(75, $analysis['completeness_score']);
        $this->assertIsArray($analysis['missing_information']);
    }

    public function test_scope_pricing_engine_calculates_dynamic_valuation()
    {
        $project = new Project(['project_name' => 'نظام إدارة عملاء CRM']);
        $features = ['إدارة العملاء', 'متابعة الصفقات', 'تصدير التقارير PDF', 'ربط مع الواتساب'];

        $valuation = $this->pricingEngine->calculateValuation($project, $features);

        $this->assertEquals('crm_system', $valuation['type_key']);
        $this->assertGreaterThan(0, $valuation['recommended_usd']);
        $this->assertGreaterThanOrEqual($valuation['min_usd'], $valuation['recommended_usd']);
        $this->assertLessThanOrEqual($valuation['max_usd'], $valuation['recommended_usd']);
        $this->assertIsArray($valuation['feature_breakdown']);
    }
}
