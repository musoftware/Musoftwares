<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\Todo;
use App\Services\AI\AiAgencyLaravelExecutor;
use App\Services\AI\AiContextBuilder;
use App\Services\AI\AiResponseValidator;
use Tests\TestCase;

class AiProjectManagerFlowTest extends TestCase
{
    public function test_ai_context_builder_assembles_full_project_snapshot(): void
    {
        $project = new Project();
        $project->id = 999;
        $project->project_name = 'Test SaaS App';
        $project->description = 'A SaaS application for agency operations';
        $project->budget = 1000;
        $project->total_paid = 500; // 50% paid
        $project->ai_context = [
            'current_stage' => 'EXECUTION',
            'current_archetype' => 'mvp_web_app',
            'approved_scope' => ['Auth Module', 'Dashboard'],
            'pending_features' => ['Payment Integration'],
        ];

        $builder = new AiContextBuilder();
        $snapshot = $builder->buildProjectSnapshot($project);

        $this->assertIsArray($snapshot);
        $this->assertEquals('EXECUTION', $snapshot['current_context']['stage']);
        $this->assertEquals(1000, $snapshot['invoice_payment_status']['budget_usd']);
        $this->assertEquals(500, $snapshot['invoice_payment_status']['total_paid_usd']);
        $this->assertTrue($snapshot['invoice_payment_status']['is_50pct_paid']);
        $this->assertArrayHasKey('company_policies', $snapshot);
    }

    public function test_ai_response_validator_applies_flow_defaults(): void
    {
        $validator = new AiResponseValidator();
        $validated = $validator->validateAndRepair(json_encode([
            'reply' => 'أهلاً بك، تفاصيل مشروعك واضحة.',
            'request_classification' => 'BUG',
            'support_check' => 'ACTIVE',
        ]));

        $this->assertEquals('BUG', $validated['request_classification']);
        $this->assertEquals('ACTIVE', $validated['support_check']);
        $this->assertArrayHasKey('admin_work', $validated);
    }

    public function test_laravel_executor_handles_admin_work_creation(): void
    {
        $project = new Project();
        $project->id = 888;
        $project->project_name = 'Flow Test Project';
        $project->budget = 2000;
        $project->total_paid = 1000;
        $project->ai_context = ['current_stage' => 'EXECUTION'];
        $project->exists = false;

        $executor = new AiAgencyLaravelExecutor();
        $decision = [
            'reply' => 'تم فتح المشـروع وتجهيز المهام.',
            'request_classification' => 'IN_SCOPE_FEATURE',
            'context_updates' => [
                'current_stage' => 'EXECUTION',
            ],
            'admin_work' => [
                'summary' => 'موقع متجر إلكتروني متكامل',
                'developer_tasks' => ['Setup Auth Module', 'Build Products Catalog'],
                'admin_todos' => ['Verify Merchant Credentials'],
                'developer_notes' => 'Use Laravel 12 and Inertia v2',
                'suggested_priorities' => ['Phase 1: Setup'],
            ],
        ];

        $executor->execute($project, $decision, 1);

        $this->assertEquals('Use Laravel 12 and Inertia v2', $project->ai_context['developer_notes'] ?? null);
        $this->assertEquals(['Phase 1: Setup'], $project->ai_context['suggested_priorities'] ?? null);
    }
}
