<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Invoice;
use App\Models\Currency;
use Tests\TestCase;

class InvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected TenantClient $client;
    protected Currency $currency;
    protected \Modules\ERP\Models\Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        config(['erp.platform_tenant_id' => 999]);

        // Re-run permission seeding and currency seeding
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
            'preferred_currency' => 'USD',
        ]);
        $this->user->assignRole('client');

        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Test Agency',
            'status' => 'active',
            'base_currency_id' => $this->currency->id,
        ]);

        session(['tenant_id' => $this->tenant->id]);

        $this->client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Acme Corp',
            'email' => 'billing@acme.com',
            'phone' => '+15551234567',
            'currency_id' => $this->currency->id,
            'address' => '123 Main St, Anytown',
        ]);

        $this->project = \Modules\ERP\Models\Project::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'name' => 'Acme Website Redesign',
            'status' => 'Active',
            'budget' => 5000.00,
            'due_date' => now()->addDays(60)->toDateString(),
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_view_invoices_index(): void
    {
        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\App\Http\Middleware\SubscriptionMiddleware::class)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->get(route('erp.invoices.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_invoice_workflow(): void
    {
        $postData = [
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'invoice_number' => 'INV-2026-001',
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_amount' => 50,
            'tax_rate' => 10,
            'notes' => 'Thank you for your business!',
            'items' => [
                [
                    'type' => 'simple',
                    'title' => 'Web Design Services',
                    'description' => 'Custom UI design and branding',
                    'unit_price' => 1000.00,
                    'quantity' => 1,
                ],
                [
                    'type' => 'quantity',
                    'title' => 'Backend Development',
                    'description' => 'Laravel REST API integration',
                    'unit_price' => 150.00,
                    'quantity' => 4,
                ]
            ],
            'costs' => [
                [
                    'title' => 'Domain Purchase',
                    'amount' => 15.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\App\Http\Middleware\SubscriptionMiddleware::class)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.invoices.store'), $postData);
        if ($response->status() !== 201 && $response->status() !== 302 && $response->status() !== 200) {
            @mkdir('scratch');
            file_put_contents('scratch/error.html', $response->getContent());
            echo "Response Status: " . $response->status() . "\n";
            echo "Errors: " . json_encode(session('errors')?->all()) . "\n";
            echo "Content written to scratch/error.html\n";
        }

        // Subtotal = (1000 * 1) + (150 * 4) = 1600
        // Discount = 50 -> Taxable = 1550
        // Tax = 10% of 1550 = 155
        // Total = 1550 + 155 = 1705

        $this->assertDatabaseHas('erp_invoices', [
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'invoice_number' => 'INV-2026-001',
            'amount' => 1705.00,
            'currency_id' => $this->currency->id,
            'discount_amount' => 50.00,
            'tax_rate' => 10.00,
            'tax_amount' => 155.00,
        ]);

        $invoice = Invoice::where('invoice_number', 'INV-2026-001')->first();

        $this->assertDatabaseHas('erp_invoice_items', [
            'invoice_id' => $invoice->id,
            'title' => 'Web Design Services',
            'total' => 1000.00,
        ]);

        $this->assertDatabaseHas('erp_invoice_costs', [
            'invoice_id' => $invoice->id,
            'title' => 'Domain Purchase',
            'amount' => 15.00,
        ]);
    }

    public function test_can_send_and_mark_paid_invoice(): void
    {
        \Modules\ERP\Models\ClientWallet::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'balance' => 1000.00,
            'currency_id' => $this->currency->id,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-2026-002',
            'client_id' => $this->client->id,
            'status' => 'draft',
            'amount' => 500.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 500.00,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Send invoice
        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\App\Http\Middleware\SubscriptionMiddleware::class)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.invoices.send', $invoice->id));

        $this->assertEquals('sent', $invoice->fresh()->status);

        // Mark paid
        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\App\Http\Middleware\SubscriptionMiddleware::class)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.invoices.mark-paid', $invoice->id));

        echo "markPaid Response Status: " . $response->status() . "\n";
        if ($response->status() === 500) {
            @mkdir('scratch');
            file_put_contents('scratch/error.html', $response->getContent());
            echo "markPaid 500 error saved to scratch/error.html\n";
        }
        if (session('errors')) {
            echo "markPaid Session Errors: " . json_encode(session('errors')->all()) . "\n";
        }
        $response->assertSessionHasNoErrors();
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_can_update_invoice(): void
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-2026-003',
            'client_id' => $this->client->id,
            'status' => 'draft',
            'amount' => 500.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 500.00,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'client_id' => $this->client->id,
            'project_id' => $this->project->id,
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'discount_amount' => 10,
            'tax_rate' => 5,
            'notes' => 'Updated invoice notes',
            'items' => [
                [
                    'type' => 'simple',
                    'title' => 'Updated Web Design',
                    'unit_price' => 1200.00,
                    'quantity' => 1,
                ]
            ],
            'costs' => []
        ];

        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\App\Http\Middleware\SubscriptionMiddleware::class)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->put(route('erp.invoices.update', $invoice->id), $updateData);

        if ($response->status() !== 302) {
            echo "Update response status: " . $response->status() . "\n";
            echo "Errors: " . json_encode(session('errors')?->all()) . "\n";
        }

        $response->assertRedirect(route('erp.invoices.show', $invoice->id));
        $this->assertDatabaseHas('erp_invoices', [
            'id' => $invoice->id,
            'amount' => 1249.50, // (1200 - 10) * 1.05 = 1190 * 1.05 = 1249.5
            'notes' => 'Updated invoice notes',
        ]);
    }
}

