<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function createClient()
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');

        return $client;
    }

    public function test_admin_can_access_invoices_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.invoices.index'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_invoices_index()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.invoices.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_invoice()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $currency = Currency::first() ?? Currency::forceCreate(['code' => 'USD', 'symbol' => '$', 'name' => 'USD']);

        $invoice = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.show', $invoice->id));

        $response->assertSuccessful();
    }

    public function test_admin_can_search_invoices_by_username_project_name_and_item_title()
    {
        $admin = $this->createAdmin();

        $client1 = User::factory()->create([
            'name' => 'John Doe Searchable',
            'telegram_username' => 'johndoetg',
            'email' => 'john.doe.searchable@example.com',
            'onboarding_completed' => true
        ]);
        $client1->assignRole('client');

        $client2 = User::factory()->create([
            'name' => 'Jane Smith',
            'telegram_username' => 'janesmithtg',
            'email' => 'jane.smith@example.com',
            'onboarding_completed' => true
        ]);
        $client2->assignRole('client');

        $currency = Currency::first() ?? Currency::forceCreate(['code' => 'USD', 'symbol' => '$', 'name' => 'USD']);

        $project = \App\Models\Project::forceCreate([
            'project_name' => 'Searchable Project Alpha',
            'user_id' => $client1->id,
            'archived' => 0,
        ]);

        $invoice1 = Invoice::forceCreate([
            'user_id' => $client1->id,
            'project_id' => $project->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $invoice2 = Invoice::forceCreate([
            'user_id' => $client2->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        \App\Models\InvoiceItem::forceCreate([
            'invoice_id' => $invoice1->id,
            'item_title' => 'Searchable Item Title X',
            'amount' => 100,
            'qty' => 1,
        ]);

        // 1. Search by customer name (client_name filter)
        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'search' => 'Searchable',
            'filter_by' => 'client_name'
        ]));
        $response->assertSuccessful();
        
        // 2. Search by telegram username (username filter)
        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'search' => 'johndoetg',
            'filter_by' => 'username'
        ]));
        $response->assertSuccessful();

        // 3. Search by item title (item_title filter)
        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'search' => 'Title X',
            'filter_by' => 'item_title'
        ]));
        $response->assertSuccessful();

        // 4. Search by project name (project_name filter)
        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'search' => 'Alpha',
            'filter_by' => 'project_name'
        ]));
        $response->assertSuccessful();

        // 5. Search using 'all' filter matching item title
        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'search' => 'Title X',
            'filter_by' => 'all'
        ]));
        $response->assertSuccessful();
    }

    public function test_admin_can_filter_invoices_by_client()
    {
        $admin = $this->createAdmin();
        $client1 = $this->createClient();
        $client2 = $this->createClient();
        $currency = Currency::first() ?? Currency::forceCreate(['code' => 'USD', 'symbol' => '$', 'name' => 'USD']);

        $invoice1 = Invoice::forceCreate([
            'user_id' => $client1->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $invoice2 = Invoice::forceCreate([
            'user_id' => $client2->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'client_id' => $client1->id,
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $invoice1->id)
        );
    }

    public function test_admin_can_filter_invoices_by_project()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();
        $currency = Currency::first() ?? Currency::forceCreate(['code' => 'USD', 'symbol' => '$', 'name' => 'USD']);

        $project1 = \App\Models\Project::forceCreate([
            'project_name' => 'Project Alpha',
            'user_id' => $client->id,
        ]);
        $project2 = \App\Models\Project::forceCreate([
            'project_name' => 'Project Beta',
            'user_id' => $client->id,
        ]);

        $invoice1 = Invoice::forceCreate([
            'user_id' => $client->id,
            'project_id' => $project1->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $invoice2 = Invoice::forceCreate([
            'user_id' => $client->id,
            'project_id' => $project2->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'project_id' => $project1->id,
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $invoice1->id)
        );
    }

    public function test_admin_can_filter_invoices_by_date_range()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();
        $currency = Currency::first() ?? Currency::forceCreate(['code' => 'USD', 'symbol' => '$', 'name' => 'USD']);

        $invoice1 = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
            'created_at' => \Carbon\Carbon::parse('2026-05-10 12:00:00'),
        ]);

        $invoice2 = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
            'created_at' => \Carbon\Carbon::parse('2026-06-15 12:00:00'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $invoice1->id)
        );
    }

    public function test_admin_can_filter_invoices_by_amount_range()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();
        $currency = Currency::first() ?? Currency::forceCreate(['code' => 'USD', 'symbol' => '$', 'name' => 'USD']);

        $invoice1 = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);
        \App\Models\InvoiceItem::forceCreate([
            'invoice_id' => $invoice1->id,
            'item_title' => 'Small Service',
            'amount' => 50.00,
            'qty' => 1,
        ]);
        $invoice1->updateCachedTotal();

        $invoice2 = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);
        \App\Models\InvoiceItem::forceCreate([
            'invoice_id' => $invoice2->id,
            'item_title' => 'Large Service',
            'amount' => 500.00,
            'qty' => 1,
        ]);
        $invoice2->updateCachedTotal();

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'min_amount' => 100,
            'max_amount' => 600,
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $invoice2->id)
        );
    }

    public function test_admin_can_filter_invoices_by_status()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();
        $currency = Currency::first() ?? Currency::forceCreate(['code' => 'USD', 'symbol' => '$', 'name' => 'USD']);

        $paidInvoice = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'paid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $unpaidInvoice = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'status' => 'paid',
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $paidInvoice->id)
        );
    }

    public function test_admin_can_filter_invoices_by_job_status()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();
        $currency = Currency::first() ?? Currency::forceCreate(['code' => 'USD', 'symbol' => '$', 'name' => 'USD']);

        $invoice1 = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'job_status' => 'done',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $invoice2 = Invoice::forceCreate([
            'user_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'unpaid',
            'job_status' => 'pending',
            'tax_value' => 0,
            'discount' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.index', [
            'job_status' => 'done',
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $invoice1->id)
        );
    }
}
