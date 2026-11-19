<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Visitors were confused: tech words in the headline, four buttons, store prices and no real work.
 * The homepage now says one thing, offers one main step, and shows work done for real clients.
 */
class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_says_what_we_do_and_shows_real_client_work(): void
    {
        $response = $this->get('/?lang=ar')->assertOk();

        $response->assertSee('بنعملك البرنامج أو الموقع أو التطبيق اللي شغلك محتاجه');
        $response->assertSee('شغل عملناه لعملاء حقيقيين');
        $response->assertSee('/portfolio/acelbay', false);
        $response->assertSee('/portfolio/food-lab', false);

        $response->assertDontSee('RaaS');
        $response->assertDontSee('hero-orbit', false);
        $response->assertDontSee('إيه اللي بنقدمه؟');
        $response->assertDontSee('مهندس');
    }
}
