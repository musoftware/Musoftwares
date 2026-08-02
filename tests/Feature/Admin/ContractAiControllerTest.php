<?php

namespace Tests\Feature\Admin;

use App\Models\AdminSettings;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContractAiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

        $this->project = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'My New Project',
            'status' => 'open',
        ]);

        AdminSettings::SetValue('default_ai_model', 'gemini');
        AdminSettings::SetValue('gemini_api_key', 'test-gemini-key');
    }

    public function test_admin_can_generate_contract_ai()
    {
        $geminiResponse = [
            'description' => 'Test Desc',
            'payment_terms' => 'Test Payments',
            'terms' => 'Test Terms',
            'notes' => 'Test Notes',
            'duration' => '2 Weeks',
            'key_features' => ['Feature 1'],
            'pricing_items' => [
                [
                    'item' => 'Setup',
                    'description' => 'Initial setup',
                    'hours' => 10,
                    'hourly_rate' => 50,
                    'total' => 500,
                ],
            ],
            'total_amount' => 500,
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($geminiResponse)]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->postJson('/admin/contracts/ai/generate', [
            'project_id' => $this->project->id,
            'prompt' => 'Make a contract for web design',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['description', 'payment_terms', 'terms', 'notes']);
    }

    public function test_admin_can_review_contract_ai()
    {
        $geminiResponse = [
            'critical_issues' => ['None'],
            'suggestions' => ['Looks good'],
            'refined_content' => 'Refined text here',
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($geminiResponse)]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->postJson('/admin/contracts/ai/review', [
            'description' => str_repeat('This is a test description long enough to pass validation.', 3), // min:50
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['critical_issues', 'suggestions', 'refined_content']]);
    }

    public function test_fails_if_api_key_missing()
    {
        AdminSettings::SetValue('gemini_api_key', null);
        AdminSettings::SetValue('gemini_api_keys', null);

        $response = $this->actingAs($this->admin)->postJson('/admin/contracts/ai/generate', [
            'project_id' => $this->project->id,
            'prompt' => 'Make a contract for web design',
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }
}
