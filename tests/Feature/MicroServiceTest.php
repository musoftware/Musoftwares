<?php

namespace Tests\Feature;

use App\Mail\AdminMicroServiceOrderMail;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\MicroService;
use App\Models\MicroServiceOrder;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MicroServiceTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Feature\Concerns\SeedsUsdEgpRates;

    protected User $admin;
    protected User $clientUser;
    protected Currency $baseCurrency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->baseCurrency = Currency::firstOrCreate(
            ['currency' => 'EGP'],
            [
                'name' => 'Egyptian Pound',
                'currency' => 'EGP',
                'symbol' => 'E£',
                'string_format' => '%01.2f E£',
                'is_default' => true,
            ]
        );

        $this->admin = User::factory()->create([
            'email' => 'admin@musoftwares.com',
            'onboarding_completed' => true,
            'currency_id' => $this->baseCurrency->id,
        ]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create([
            'email' => 'client@example.com',
            'onboarding_completed' => true,
            'currency_id' => $this->baseCurrency->id,
        ]);
        $this->clientUser->assignRole('client');
    }

    public function test_client_can_view_micro_services_catalog(): void
    {
        $service = MicroService::create([
            'title' => 'تهيئة وإعداد ووردبريس',
            'description' => 'تنصيب نظام ووردبريس وربطه بقاعدة البيانات',
            'price' => 150.00,
            'currency_id' => $this->baseCurrency->id,
            'delivery_days' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->clientUser)->get(route('micro-services.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Client/MicroServices/Index')
            ->has('services', 1)
            ->where('services.0.title', 'تهيئة وإعداد ووردبريس')
        );
    }

    public function test_client_cannot_order_without_sufficient_balance(): void
    {
        $service = MicroService::create([
            'title' => 'تثبيت شهادة SSL',
            'description' => 'تثبيت شهادة SSL وتفعيل HTTPS',
            'price' => 200.00,
            'currency_id' => $this->baseCurrency->id,
            'delivery_days' => 1,
            'is_active' => true,
        ]);

        // User has 0 balance
        $response = $this->actingAs($this->clientUser)
            ->post(route('micro-services.order', $service->id), [
                'requirements' => 'الرجاء تثبيت شهادة SSL للنطاق example.com',
            ]);

        $response->assertSessionHasErrors(['balance']);
        $this->assertDatabaseCount('micro_service_orders', 0);
    }

    public function test_client_can_order_with_sufficient_balance_and_admin_is_emailed(): void
    {
        Mail::fake();

        $service = MicroService::create([
            'title' => 'تثبيت شهادة SSL',
            'description' => 'تثبيت شهادة SSL وتفعيل HTTPS',
            'price' => 200.00,
            'currency_id' => $this->baseCurrency->id,
            'delivery_days' => 1,
            'is_active' => true,
        ]);

        // Deposit 500 EGP into client's wallet
        $this->clientUser->add_balance(500.00, 'Initial Deposit', 'deposit', $this->baseCurrency->id);

        $initialBalance = $this->clientUser->available_balance();
        $this->assertEquals(500.00, $initialBalance);

        $response = $this->actingAs($this->clientUser)
            ->post(route('micro-services.order', $service->id), [
                'requirements' => 'الرجاء تثبيت شهادة SSL للنطاق example.com والتحويل التلقائي',
            ]);

        $response->assertRedirect(route('micro-services.index'));
        $response->assertSessionHas('success');

        // Verify balance was deducted
        $this->clientUser->refresh();
        $this->assertEquals(300.00, $this->clientUser->available_balance());

        // Verify order was created in DB
        $this->assertDatabaseHas('micro_service_orders', [
            'user_id' => $this->clientUser->id,
            'micro_service_id' => $service->id,
            'amount_paid' => 200.00,
            'currency_id' => $this->baseCurrency->id,
            'status' => 'pending',
            'requirements' => 'الرجاء تثبيت شهادة SSL للنطاق example.com والتحويل التلقائي',
        ]);

        // Verify email was queued/sent to admin
        Mail::assertQueued(AdminMicroServiceOrderMail::class, function ($mail) {
            return $mail->order->amount_paid == 200.00;
        });
    }

    public function test_multi_currency_order_conversion(): void
    {
        Mail::fake();

        // Real seeded currencies (USD = 1, EGP = 2) so the wallet ledger can convert to the EGP business currency.
        $usd = Currency::where('currency', 'USD')->firstOrFail();

        // Exchange rate: 1 USD = 50 EGP (1 EGP = 0.02 USD)
        CurrenciesExchange::updateOrCreate(
            ['currency1' => $this->baseCurrency->id, 'currency2' => $usd->id, 'date_string' => now('Africa/Cairo')->toDateString()],
            ['rate' => 0.02]
        );
        CurrenciesExchange::updateOrCreate(
            ['currency1' => $usd->id, 'currency2' => $this->baseCurrency->id, 'date_string' => now('Africa/Cairo')->toDateString()],
            ['rate' => 50.0]
        );
        CurrenciesExchange::flushCache();

        // Service priced at 500 EGP (= 10 USD)
        $service = MicroService::create([
            'title' => 'فحص الأمان والتهيئة',
            'description' => 'فحص كامل للثغرات وضبط الجدار الناري',
            'price' => 500.00,
            'currency_id' => $this->baseCurrency->id,
            'delivery_days' => 2,
            'is_active' => true,
        ]);

        // User with USD currency wallet
        $usdUser = User::factory()->create([
            'email' => 'usdclient@example.com',
            'onboarding_completed' => true,
            'currency_id' => $usd->id,
        ]);
        $usdUser->assignRole('client');

        // Give user $20 USD balance
        $usdUser->add_balance(20.00, 'USD Deposit', 'deposit', $usd->id);

        $response = $this->actingAs($usdUser)
            ->post(route('micro-services.order', $service->id), [
                'requirements' => 'Server IP: 1.2.3.4, need full audit',
            ]);

        $response->assertRedirect(route('micro-services.index'));
        $this->assertDatabaseHas('micro_service_orders', [
            'user_id' => $usdUser->id,
            'micro_service_id' => $service->id,
            'amount_paid' => 10.00,
            'currency_id' => $usd->id,
            'status' => 'pending',
        ]);

        $usdUser->refresh();
        $this->assertEquals(10.00, $usdUser->available_balance());
    }

    public function test_admin_can_manage_services_and_complete_orders(): void
    {
        $service = MicroService::create([
            'title' => 'خدمة تجريبية',
            'description' => 'وصف تجريبي',
            'price' => 100.00,
            'currency_id' => $this->baseCurrency->id,
            'delivery_days' => 1,
            'is_active' => true,
        ]);

        $order = MicroServiceOrder::create([
            'user_id' => $this->clientUser->id,
            'micro_service_id' => $service->id,
            'amount_paid' => 100.00,
            'base_amount' => 100.00,
            'currency_id' => $this->baseCurrency->id,
            'requirements' => 'تفاصيل الطلب',
            'status' => 'pending',
        ]);

        // Admin views index
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.micro-services.index'));
        $indexResponse->assertStatus(200);

        // Admin creates a new service
        $createResponse = $this->actingAs($this->admin)->post(route('admin.micro-services.store'), [
            'title' => 'خدمة جديدة من الأدمن',
            'description' => 'وصف سطر واحد للخدمة الجديدة',
            'price' => 350.00,
            'delivery_days' => 2,
            'is_active' => true,
        ]);
        $createResponse->assertRedirect();
        $this->assertDatabaseHas('micro_services', [
            'title' => 'خدمة جديدة من الأدمن',
            'price' => 350.00,
        ]);

        // Admin marks the order as completed
        $completeResponse = $this->actingAs($this->admin)
            ->post(route('admin.micro-services.orders.complete', $order->id), [
                'admin_notes' => 'تم تنفيذ الخدمة بنجاح ورفع الملفات.',
            ]);

        $completeResponse->assertRedirect();
        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('تم تنفيذ الخدمة بنجاح ورفع الملفات.', $order->admin_notes);
        $this->assertNotNull($order->completed_at);
    }
}
