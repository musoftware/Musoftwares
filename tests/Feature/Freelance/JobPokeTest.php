<?php

namespace Tests\Feature\Freelance;

use Modules\Freelance\Models\Job;
use Illuminate\Support\Facades\Event;

class JobPokeTest extends FreelanceTestCase
{
    public function test_freelancer_can_poke_client_on_job(): void
    {
        $job = Job::factory()->create([
            'client_id' => $this->clientUser->id,
            'status' => 'open',
        ]);

        \Modules\Freelance\Models\Proposal::factory()->create([
            'job_id' => $job->id,
            'freelancer_id' => $this->freelancer1->id,
        ]);

        $response = $this->actingAs($this->freelancer1)
            ->post(route('freelance.jobs.poke', $job->id));

        $response->assertStatus(302);
        
        // Assert session has success to confirm poke was sent
        $response->assertSessionHas('success');
    }
}
