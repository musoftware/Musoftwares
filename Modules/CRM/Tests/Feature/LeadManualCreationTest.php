<?php

namespace Modules\CRM\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\CRM\Tests\Support\BaseTenantTestCase;
use Modules\CRM\Models\Lead;

class LeadManualCreationTest extends BaseTenantTestCase
{
    use DatabaseTransactions;

    public function test_can_create_lead_manually()
    {
        // Give only sales-staff addon to allow accessing CRM
        UserSubscription::create([
            'user_id' => $this->adminUser->id,
            'object' => 'crm',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($this->adminUser);
        $this->withoutExceptionHandling();

        // Submit the form
        $response = $this->withSession(['crm_workspace_id' => $this->workspace->id])->post('/crm/leads', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'company' => 'Google',
            'message' => 'Hello I need your services',
        ]);

        // Assert it redirects back with success flash message
        $response->assertStatus(302);
        $response->assertSessionHas('success', __('crm.lead_created_success'));

        // Assert lead was created in DB with correct values and scoping
        $lead = Lead::where('name', 'John Doe')->first();
        $this->assertNotNull($lead);
        $this->assertEquals('john@example.com', $lead->email);
        $this->assertEquals('1234567890', $lead->phone);
        $this->assertEquals('Google', $lead->company);
        $this->assertEquals('Hello I need your services', $lead->message);
        $this->assertEquals($this->workspace->id, $lead->workspace_id);
        $this->assertEquals('new', $lead->status);
        $this->assertEquals('NEW', $lead->pipeline_stage);
        $this->assertEquals('Manual', $lead->source);
        $this->assertEquals($this->adminUser->id, $lead->assigned_to);
    }

    public function test_create_lead_manually_requires_name()
    {
        UserSubscription::create([
            'user_id' => $this->adminUser->id,
            'object' => 'crm',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->withSession(['crm_workspace_id' => $this->workspace->id])->post('/crm/leads', [
            'name' => '',
            'email' => 'john@example.com',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseMissing('leads', ['email' => 'john@example.com']);
    }
}
