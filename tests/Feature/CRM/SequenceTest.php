<?php

namespace Tests\Feature\CRM;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Modules\CRM\Models\Sequence;

class SequenceTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    public function test_sequence_multi_tenancy_isolation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $workspace1 = \Modules\CRM\Models\Workspace::create(['user_id' => $user1->id, 'name' => 'WS1']);
        $workspace2 = \Modules\CRM\Models\Workspace::create(['user_id' => $user2->id, 'name' => 'WS2']);
        
        Sequence::create(['workspace_id' => $workspace1->id, 'name' => 'Sequence 1']);
        Sequence::create(['workspace_id' => $workspace2->id, 'name' => 'Sequence 2']);
        
        $this->actingAs($user1);
        app(\Modules\CRM\Infrastructure\Context\TenantContext::class)->setWorkspaceId($workspace1->id);
        
        $sequences = Sequence::all();
        $this->assertCount(1, $sequences);
        $this->assertEquals('Sequence 1', $sequences->first()->name);
    }
    
    public function test_sequence_controller_uses_correct_models()
    {
        $this->markTestSkipped('Sequence steps feature is not yet implemented.');
        
        $user = User::factory()->create();
        $workspace = \Modules\CRM\Models\Workspace::create(['user_id' => $user->id, 'name' => 'WS']);
        $sequence = Sequence::create(['workspace_id' => $workspace->id, 'name' => 'Sequence 1']);
        
        $this->withoutMiddleware();
        app(\Modules\CRM\Infrastructure\Context\TenantContext::class)->setWorkspaceId($workspace->id);
        
        $this->actingAs($user)->post(route('crm.sequences.steps.store', $sequence->id), [
            'name' => 'Test Step',
            'order' => 1,
            'type' => 'email',
            'delay_days' => 1
        ]);
        
        $this->assertDatabaseHas('crm_sequence_steps', [
            'sequence_id' => $sequence->id,
            'name' => 'Test Step'
        ]);
    }
}
