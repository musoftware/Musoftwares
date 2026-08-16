<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AI\ProjectEstimatorAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EstimatorAiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimator_page_is_publicly_accessible()
    {
        $response = $this->get('/estimator');
        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_ai_estimator_analyzer()
    {
        $response = $this->postJson('/estimator/ai-analyze', [
            'prompt' => 'Building a multi-vendor marketplace with mobile app and payment gateway',
        ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_ai_estimator_analyzer()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/estimator/ai-analyze', [
            'prompt' => 'Building a multi-vendor marketplace with mobile app and payment gateway',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_analyze_project_with_ai()
    {
        Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Mock the AI service
        $mockAiService = Mockery::mock(ProjectEstimatorAiService::class);
        $mockAiService->shouldReceive('analyze')
            ->once()
            ->with('Building a multi-vendor e-commerce store with mobile app and payment gateways')
            ->andReturn([
                'platforms' => ['web', 'mobile'],
                'platformScreens' => ['web' => 8, 'mobile' => 12, 'desktop' => 5],
                'selectedOptions' => [
                    'web_admin_panel' => 1,
                    'web_gateways' => 2,
                    'mobile_push_notifications' => 1,
                ],
                'summary_ar' => 'متجر إلكتروني مع تطبيق موبايل وبوابات دفع',
                'summary_en' => 'E-commerce platform with mobile app and payment gateways',
                'recommended_reasons' => ['E-commerce requires online checkout and mobile app.'],
            ]);

        $this->app->instance(ProjectEstimatorAiService::class, $mockAiService);

        $response = $this->actingAs($admin)->postJson('/estimator/ai-analyze', [
            'prompt' => 'Building a multi-vendor e-commerce store with mobile app and payment gateways',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'platforms' => ['web', 'mobile'],
                    'platformScreens' => [
                        'web' => 8,
                        'mobile' => 12,
                    ],
                    'selectedOptions' => [
                        'web_admin_panel' => 1,
                        'web_gateways' => 2,
                    ],
                ],
            ]);
    }
}
