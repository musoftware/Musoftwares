<?php

namespace Tests\Feature;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuotationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Currency $currencyUsd;
    protected Currency $currencyEgp;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $clientRole = Role::firstOrCreate(['name' => 'client']);

        $this->currencyUsd = Currency::firstOrCreate([
            'currency' => 'USD',
        ], [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_default' => true,
        ]);

        $this->currencyEgp = Currency::firstOrCreate([
            'currency' => 'EGP',
        ], [
            'name' => 'Egyptian Pound',
            'symbol' => 'EGP',
            'exchange_rate' => 50.0,
            'is_default' => false,
        ]);

        CurrenciesExchange::firstOrCreate([
            'currency1' => $this->currencyUsd->id,
            'currency2' => $this->currencyEgp->id,
        ], [
            'rate' => 50.0,
            'date_string' => now()->toDateString(),
        ]);

        CurrenciesExchange::firstOrCreate([
            'currency1' => $this->currencyEgp->id,
            'currency2' => $this->currencyUsd->id,
        ], [
            'rate' => 0.02,
            'date_string' => now()->toDateString(),
        ]);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@musoftwares.com',
            'currency_id' => $this->currencyUsd->id,
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    public function test_admin_can_view_quotations_index()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.marketplace.quotations.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Quotations/Index'));
    }

    public function test_admin_can_create_quotation_with_hybrid_items_and_recalculates_totals()
    {
        $payload = [
            'title' => 'عرض سعر تطوير متجر وتطبيق',
            'currency' => 'USD',
            'currency_id' => $this->currencyUsd->id,
            'deposit_percentage' => 50,
            'status' => 'active',
            'scope_markdown' => '### نطاق العمل\n- برمجة المتجر',
            'notes' => 'ملاحظات داخلية',
            'items' => [
                [
                    'type' => 'our_work',
                    'title' => 'برمجة المتجر الإلكتروني',
                    'description' => 'تطوير الواجهات والباك إند',
                    'price' => 2000,
                    'quantity' => 1,
                ],
                [
                    'type' => 'our_work',
                    'title' => 'تطبيق الجوال',
                    'description' => 'تطوير Flutter',
                    'price' => 1000,
                    'quantity' => 1,
                ],
                [
                    'type' => 'indicative_cost',
                    'title' => 'استضافة VPS Hetzner',
                    'description' => 'تكلفة سنوية',
                    'price' => 150,
                    'quantity' => 1,
                    'external_link' => 'https://hetzner.com',
                    'link_label' => 'حجز الاستضافة',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.marketplace.quotations.store'), $payload);

        $response->assertRedirect();

        $quotation = Quotation::where('title', 'عرض سعر تطوير متجر وتطبيق')->first();
        $this->assertNotNull($quotation);
        $this->assertEquals(3000.00, (float) $quotation->development_total);
        $this->assertEquals(150.00, (float) $quotation->indicative_total);
        $this->assertEquals(3150.00, (float) $quotation->grand_total);
        $this->assertEquals(1500.00, (float) $quotation->deposit_amount);
        $this->assertEquals(1500.00, (float) $quotation->remaining_amount);
        $this->assertNotNull($quotation->quotation_number);
        $this->assertNotNull($quotation->uuid);
    }

    public function test_public_guest_can_view_quotation_and_increments_views()
    {
        $quotation = Quotation::create([
            'title' => 'عرض عام متاح للجميع',
            'created_by_user_id' => $this->adminUser->id,
            'currency_id' => $this->currencyUsd->id,
            'currency' => 'USD',
            'deposit_percentage' => 50,
            'status' => 'active',
            'development_total' => 1000,
            'deposit_amount' => 500,
        ]);

        $this->assertEquals(0, $quotation->views_count);

        $response = $this->get(route('guest.quotations.show', ['uuid' => $quotation->uuid]));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Guest/QuotationShow'));

        $quotation->refresh();
        $this->assertEquals(1, $quotation->views_count);
        $this->assertNotNull($quotation->last_viewed_at);
    }

    public function test_client_checkout_creates_user_and_invoice_and_order()
    {
        $this->withoutExceptionHandling();

        $quotation = Quotation::create([
            'title' => 'عرض برمجة منصة',
            'created_by_user_id' => $this->adminUser->id,
            'currency_id' => $this->currencyUsd->id,
            'currency' => 'USD',
            'deposit_percentage' => 50,
            'status' => 'active',
            'development_total' => 2000,
            'deposit_amount' => 1000,
            'remaining_amount' => 1000,
        ]);

        $checkoutData = [
            'client_name' => 'محمد علي',
            'client_email' => 'client.mohamed@example.com',
            'client_phone' => '01012345678',
            'client_whatsapp' => '01012345678',
            'company_name' => 'شركة التقنية الذكية',
            'notes' => 'نود البدء مطلع الأسبوع القادم',
        ];

        $response = $this->post(route('guest.quotations.pay', ['uuid' => $quotation->uuid]), $checkoutData);

        // Verify User was created
        $user = User::where('email', 'client.mohamed@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('محمد علي', $user->name);
        $this->assertEquals('01012345678', $user->mobile_1);

        // Verify QuotationOrder was created
        $order = QuotationOrder::where('client_email', 'client.mohamed@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals($quotation->id, $order->quotation_id);
        $this->assertEquals(1000.00, (float) $order->deposit_amount);
        $this->assertEquals('pending_payment', $order->status);
        $this->assertNotNull($order->invoice_id);

        // Verify official Invoice was created for this user
        $invoice = Invoice::find($order->invoice_id);
        $this->assertNotNull($invoice);
        $this->assertEquals($user->id, $invoice->user_id);
        $this->assertEquals('unpaid', $invoice->status);
    }

    public function test_payment_success_marks_order_and_invoice_as_paid()
    {
        $quotation = Quotation::create([
            'title' => 'عرض تجريبي',
            'created_by_user_id' => $this->adminUser->id,
            'currency_id' => $this->currencyUsd->id,
            'currency' => 'USD',
            'deposit_percentage' => 50,
            'status' => 'active',
            'development_total' => 1000,
            'deposit_amount' => 500,
        ]);

        $user = User::factory()->create([
            'email' => 'paying.client@example.com',
            'currency_id' => $this->currencyUsd->id,
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'status' => 'unpaid',
            'currency' => $this->currencyUsd->id,
        ]);

        $order = QuotationOrder::create([
            'quotation_id' => $quotation->id,
            'user_id' => $user->id,
            'client_name' => 'العميل المسدد',
            'client_email' => 'paying.client@example.com',
            'deposit_amount' => 500,
            'currency' => 'USD',
            'status' => 'pending_payment',
            'invoice_id' => $invoice->id,
        ]);

        $response = $this->get(route('guest.quotations.payment.success', [
            'orderUuid' => $order->uuid,
            'paymentId' => 'PAY-123456',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Guest/QuotationPaymentResult'));

        $order->refresh();
        $invoice->refresh();

        $this->assertEquals('paid', $order->status);
        $this->assertEquals('kashier', $order->payment_gateway);
        $this->assertEquals('paid', $invoice->status);
    }
}
