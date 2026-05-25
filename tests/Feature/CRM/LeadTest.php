<?php

namespace Tests\Feature\CRM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Modules\CRM\Models\Lead;

class LeadTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_view_own_leads()
    {
        $user = User::factory()->create();
        Lead::factory()->create(['user_id' => $user->id, 'name' => 'John Doe']);
        
        $this->actingAs($user)
            ->get(route('crm.leads.index'))
            ->assertStatus(200)
            ->assertSee('John Doe');
    }

    public function test_user_cannot_view_other_tenant_leads()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Lead::factory()->create(['user_id' => $user1->id, 'name' => 'Tenant 1 Lead']);
        Lead::factory()->create(['user_id' => $user2->id, 'name' => 'Tenant 2 Lead']);
        
        $this->actingAs($user1)
            ->get(route('crm.leads.index'))
            ->assertStatus(200)
            ->assertSee('Tenant 1 Lead')
            ->assertDontSee('Tenant 2 Lead');
    }

    public function test_lead_deletion_cascades_and_cleans_up()
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['user_id' => $user->id]);
        
        // Mock a SequenceState if factory doesn't exist yet
        \DB::table('sequence_states')->insert([
            'sequence_id' => 1,
            'assignable_type' => Lead::class,
            'assignable_id' => $lead->id,
            'status' => 'active',
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
