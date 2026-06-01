<?php

namespace Modules\CRM\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\CRM\Models\Workspace;
use Modules\CRM\Models\CrmWidget;
use App\Models\User;

class CrmWidgetTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        // Give the user the CRM subscription
        \App\Models\UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'crm',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->workspace = Workspace::create([
            'user_id' => $this->user->id,
            'name' => 'Default Workspace',
            'is_default' => true,
        ]);
        
        $role = \Modules\CRM\Models\Role::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Owner',
            'is_system' => true,
        ]);

        $this->workspace->users()->attach($this->user->id, [
            'role_id' => $role->id,
        ]);
    }

    public function test_can_view_widgets_index()
    {
        $response = $this->actingAs($this->user)->withSession(['workspace_id' => $this->workspace->id])->get(route('crm.widgets.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_widget()
    {
        $payload = [
            'name' => 'Test Form',
            'allowed_domains' => ['http://localhost'],
            'is_active' => true,
            'form_config' => [
                'title' => 'Contact Us',
                'description' => 'Please leave a message.',
                'button_text' => 'Submit',
                'primary_color' => '#000000',
                'fields' => [
                    'name' => ['enabled' => true, 'required' => true, 'label' => 'Name'],
                    'email' => ['enabled' => true, 'required' => true, 'label' => 'Email'],
                ]
            ],
        ];

        $response = $this->actingAs($this->user)->withSession(['workspace_id' => $this->workspace->id])->post(route('crm.widgets.store'), $payload);
        
        $response->assertRedirect(route('crm.widgets.index'));
        $this->assertDatabaseHas('crm_widgets', [
            'name' => 'Test Form',
            'workspace_id' => $this->workspace->id,
        ]);
    }

    public function test_can_capture_lead_via_widget()
    {
        $widget = CrmWidget::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Widget',
            'embed_token' => \Illuminate\Support\Str::uuid()->toString(),
            'allowed_domains' => ['example.com'],
            'is_active' => true,
            'form_config' => [
                'fields' => [
                    'name' => ['enabled' => true, 'required' => true, 'label' => 'Name'],
                    'email' => ['enabled' => true, 'required' => true, 'label' => 'Email'],
                ]
            ],
        ]);

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Test Company',
            'message' => 'Hello World',
        ];
        $response = $this->post(route('crm.widgets.submit', $widget->embed_token), $payload);
        
        $response->assertSessionHasNoErrors();
        if (session('error')) {
            dump(session('error'));
        }
        $response->assertRedirect();
        
        $this->assertDatabaseHas('leads', [
            'workspace_id' => $this->workspace->id,
            'crm_widget_id' => $widget->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'source' => 'widget',
        ]);
    }
}
