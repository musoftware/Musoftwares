<?php

namespace Tests\Feature\CRM;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Workspace;

class LeadTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected function createUserWithWorkspaceAndSubscription()
    {
        $user = User::factory()->create([
            'onboarding_completed' => true,
        ]);

        $workspace = Workspace::create([
            'user_id' => $user->id,
            'name' => 'Test Workspace',
        ]);

        $planId = \DB::table('module_plans')->insertGetId([
            'module' => 'crm',
            'name' => 'CRM Premium',
            'price' => 20.00,
            'billing' => 'monthly',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('user_subscriptions')->insert([
            'user_id' => $user->id,
            'object' => 'crm',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create dummy whatsapp_channels table if missing to prevent SQLite foreign key constraint compile issues
        if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_channels')) {
            \Illuminate\Support\Facades\Schema::create('whatsapp_channels', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        return [$user, $workspace];
    }

    public function test_user_can_view_own_leads()
    {
        [$user, $workspace] = $this->createUserWithWorkspaceAndSubscription();

        Lead::factory()->create([
            'workspace_id' => $workspace->id,
            'name' => 'John Doe'
        ]);
        
        $this->actingAs($user)
            ->get(route('crm.leads.index'))
            ->assertStatus(200)
            ->assertSee('John Doe');
    }

    public function test_user_cannot_view_other_tenant_leads()
    {
        [$user1, $workspace1] = $this->createUserWithWorkspaceAndSubscription();
        [$user2, $workspace2] = $this->createUserWithWorkspaceAndSubscription();
        
        Lead::factory()->create(['workspace_id' => $workspace1->id, 'name' => 'Tenant 1 Lead']);
        Lead::factory()->create(['workspace_id' => $workspace2->id, 'name' => 'Tenant 2 Lead']);
        
        $response = $this->actingAs($user1)
            ->get(route('crm.leads.index'))
            ->assertStatus(200);
            
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('CRM/Leads/Index')
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Tenant 1 Lead')
        );
    }

    public function test_lead_deletion_cascades_and_cleans_up()
    {
        [$user, $workspace] = $this->createUserWithWorkspaceAndSubscription();

        $lead = Lead::factory()->create(['workspace_id' => $workspace->id]);
        
        $sequenceId = \DB::table('sequences')->insertGetId([
            'name' => 'Test Sequence',
            'trigger_type' => 'on_lead_creation',
            'workspace_id' => $workspace->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('sequence_states')->insert([
            'sequence_id' => $sequenceId,
            'assignable_type' => Lead::class,
            'assignable_id' => $lead->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertDatabaseHas('sequence_states', ['assignable_id' => $lead->id]);
        
        $lead->delete(); // Triggers the deleting event we added
        
        $this->assertDatabaseMissing('sequence_states', [
            'assignable_type' => Lead::class,
            'assignable_id' => $lead->id,
            'deleted_at' => null // Depending on softDeletes
        ]);
    }
}
