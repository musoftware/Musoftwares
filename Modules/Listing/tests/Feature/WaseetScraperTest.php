<?php

namespace Modules\Listing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Listing\Models\Listing;
use Modules\Listing\Notifications\ListingAuthorRegisteredNotification;
use Modules\Listing\Services\AutoUserRegistrationService;
use Modules\Listing\Services\WaseetScraperService;
use Tests\TestCase;

class WaseetScraperTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test parsing HTML details page containing Waseet's serialized Next.js state.
     */
    public function test_scraper_service_parses_valid_html_payload(): void
    {
        // Mock HTML where double quotes inside JS string are escaped as \"
        $mockHtml = '<script>self.__next_f.push([1,"18:ad_details\n \"ad\":{\"id\":12154186,\"title\":\"مطلوب سكرتيرة لعيادة طبيبة نفسية\",\"description\":\"مطلوب سكرتيرة سنها من ٣٥ - ٤٥ سنة\\nالعمل من ١١ ص- ٣ ظهرا\",\"price\":1500,\"currency\":\"ج.م\",\"phone_number\":\"+201223421182\",\"city\":\"الإسكندرية\",\"attributes\":{\"email\":{\"value\":\"amydonia@yahoo.com\"}},\"images\":[]}\n"])</script>';

        $service = new WaseetScraperService();
        $parsed = $service->parseListingDetail($mockHtml);

        $this->assertNotNull($parsed);
        $this->assertEquals(12154186, $parsed['waseet_id']);
        $this->assertEquals('مطلوب سكرتيرة لعيادة طبيبة نفسية', $parsed['title']);
        $this->assertEquals("مطلوب سكرتيرة سنها من ٣٥ - ٤٥ سنة\nالعمل من ١١ ص- ٣ ظهرا", $parsed['description']);
        $this->assertEquals('+201223421182', $parsed['phone']);
        $this->assertEquals('amydonia@yahoo.com', $parsed['email']);
        $this->assertEquals('الإسكندرية', $parsed['city']);
        $this->assertEquals(1500, $parsed['price']);
    }

    /**
     * Test that user is registered automatically and listing is saved under their profile.
     */
    public function test_auto_user_registration_service_registers_and_posts_correctly(): void
    {
        Notification::fake();

        $adData = [
            'waseet_id' => 999999,
            'title' => 'مطلوب مهندس مبيعات',
            'description' => 'مطلوب مهندس مبيعات للعمل بمدينة القاهرة خبرة 3 سنوات',
            'price' => 8000,
            'currency' => 'ج.م',
            'phone' => '+201111111111',
            'email' => 'sales-eng@company.com',
            'city' => 'القاهرة',
            'images' => ['https://wassets.waseet.net/img.jpg'],
            'original_url' => 'https://eg.waseet.net/ar/post/999999-sales-engineer',
        ];

        $service = new AutoUserRegistrationService();
        $listing = $service->registerAndPost($adData);

        // 1. Assert listing was saved
        $this->assertDatabaseHas('listings', [
            'waseet_id' => 999999,
            'title' => 'مطلوب مهندس مبيعات',
            'phone' => '+201111111111',
            'email' => 'sales-eng@company.com',
        ]);

        // 2. Assert user was created
        $user = User::where('email', 'sales-eng@company.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('+201111111111', $user->phone_number);

        // 3. Assert user was automatically subscribed to the listing module
        $this->assertTrue($user->hasModuleSubscription('listing'));

        // 4. Assert welcome email notification was dispatched
        Notification::assertSentTo($user, ListingAuthorRegisteredNotification::class);
    }

    /**
     * Test listings dashboard route authorization and subscription guards.
     */
    public function test_listings_dashboard_requires_subscription_and_auth(): void
    {
        // 1. Guest is redirected to login
        $this->get('/listing/dashboard')
            ->assertRedirect('/login');

        // 2. Authenticated user without listing subscription is redirected to pricing plans
        $userWithoutSubscription = User::factory()->create();
        $this->actingAs($userWithoutSubscription)
            ->get('/listing/dashboard')
            ->assertRedirect(route('subscriptions.plans', ['module' => 'listing']));

        // 3. Authenticated user WITH listing subscription can access
        $userWithSubscription = User::factory()->create();
        $userWithSubscription->subscriptions()->create([
            'object' => 'listing',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($userWithSubscription)
            ->get('/listing/dashboard')
            ->assertStatus(200);
    }

    /**
     * Test public pages are accessible to anyone without auth/subscription.
     */
    public function test_public_listings_and_details_do_not_require_auth(): void
    {
        $owner = User::factory()->create();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'waseet_id' => 888888,
            'title' => 'مطلوب محاسب',
            'description' => 'مطلوب محاسب مالي للشركة بمدينة الإسكندرية',
            'price' => 5000,
            'currency' => 'ج.م',
            'phone' => '+201222222222',
            'email' => 'finance@company.com',
            'original_url' => 'https://eg.waseet.net/ar/post/888888-accountant',
            'status' => 'active',
        ]);

        // 1. Index page is visible
        $this->get('/listing')
            ->assertStatus(200);

        // 2. Detail page is visible
        $this->get("/listing/{$listing->id}")
            ->assertStatus(200);
    }
}
