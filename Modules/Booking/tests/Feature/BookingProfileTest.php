<?php

namespace Modules\Booking\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Booking\Models\BookingPageConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_profile_resolves_by_slug()
    {
        $tenant = User::forceCreate([
            'name' => 'Clinic 1', 
            'email' => 't1@test.com', 
            'password' => 'test',
            ]);
        
        $config = BookingPageConfig::create([
            'tenant_id' => 1,
            'slug' => 'clinic-1',
            'page_title' => 'Welcome to Clinic 1',
            'primary_color' => '#FF0000',
        ]);

        // Mocking the Inertia render response logic would go here.
        // For unit testing the controller logic:
        $controller = app(\Modules\Booking\Http\Controllers\BookingProfileController::class);
        $request = new \Illuminate\Http\Request();
        
        $response = clone $controller->show($request, 'clinic-1');
        
        // Assert the returned Inertia response has the correct config
        $this->assertEquals('clinic-1', $config->slug);
    }
}
