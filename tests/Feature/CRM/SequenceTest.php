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
        
        // This requires the sequence migration to be run
        Sequence::factory()->create(['user_id' => $user1->id, 'name' => 'Sequence 1']);
        Sequence::factory()->create(['user_id' => $user2->id, 'name' => 'Sequence 2']);
        
        $this->actingAs($user1);
        $sequences = Sequence::all();
        $this->assertCount(1, $sequences);
        $this->assertEquals('Sequence 1', $sequences->first()->name);
    }
    
    public function test_sequence_controller_uses_correct_models()
    {
        // Assert that we don't get Target class [PlatformSequence] does not exist error
        $user = User::factory()->create();
        $sequence = Sequence::factory()->create(['user_id' => $user->id]);
        
        $this->actingAs($user)->post(route('crm.sequences.steps.store', $sequence->id), [
            'name' => 'Test Step',
            'order' => 1,
            'type' => 'email',
            'delay_days' => 1
        ]);
        
        $this->assertDatabaseHas('sequence_steps', [
            'sequence_id' => $sequence->id,
            'name' => 'Test Step'
        ]);
    }
}
