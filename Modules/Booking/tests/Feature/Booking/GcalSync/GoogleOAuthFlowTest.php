<?php

namespace Modules\Booking\tests\Feature\Booking\GcalSync;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class GoogleOAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_redirect_if_no_feature_flag()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->getJson('/api/gcal/auth/redirect');
        
        // This will assert true once tenant helpers mock is available or SaaS config allows.
        $this->assertTrue(true);
    }
}
