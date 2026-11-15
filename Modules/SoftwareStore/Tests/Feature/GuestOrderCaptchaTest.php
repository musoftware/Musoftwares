<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareOrder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Tests\TestCase;

/** A bot filled the order form with fake names; now a visitor who is not signed in must pass "I am not a robot". */
class GuestOrderCaptchaTest extends TestCase
{
    use RefreshDatabase;

    private const FORM = [
        'client_name' => 'Jordansal',
        'client_email' => 'zpaexqqq@example.com',
        'client_whatsapp' => '82478583159',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Currency::firstOrCreate(['currency' => 'EGP'], ['symbol' => 'EGP', 'string_format' => '%v EGP', 'rate' => 1]);
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::where('slug', 'gridreader')->firstOrFail()->update(['is_active' => true, 'price' => 1500]);
        config([
            'services.recaptcha.guest_orders' => true,
            'services.recaptcha.site_key' => 'site-key-for-tests',
            'services.recaptcha.secret_key' => 'secret-for-tests',
        ]);
    }

    public function test_the_order_form_shows_the_robot_check_to_visitors_only(): void
    {
        $this->get('/services/gridreader')->assertOk()->assertSee('data-sitekey="site-key-for-tests"', false);

        $this->actingAs(User::factory()->create())
            ->get('/services/gridreader')->assertOk()->assertDontSee('g-recaptcha', false);
    }

    public function test_an_order_without_the_robot_check_is_refused(): void
    {
        $this->post('/services/gridreader/order', self::FORM)->assertSessionHasErrors('g-recaptcha-response');

        $this->assertSame(0, SoftwareOrder::count());
    }

    public function test_a_failed_robot_check_is_refused_and_a_passed_one_makes_the_order(): void
    {
        Http::fake(['www.google.com/recaptcha/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

        $this->post('/services/gridreader/order', self::FORM + ['g-recaptcha-response' => 'bad'])
            ->assertSessionHasErrors('g-recaptcha-response');
        $this->assertSame(0, SoftwareOrder::count());

        $this->post('/services/gridreader/order', self::FORM + ['g-recaptcha-response' => 'good'])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame(1, SoftwareOrder::count());
    }
}
