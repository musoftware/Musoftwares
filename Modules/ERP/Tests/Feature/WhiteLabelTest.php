<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Invoice;
use Tests\TestCase;

class WhiteLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_pdf_shows_branding_without_white_label_addon()
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $owner->id, 'name' => 'Test Tenant', 'status' => 'active']);
        $client = \Modules\ERP\Models\TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'client@test.com', 'currency_id' => 1]);
        $invoice = Invoice::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-001', 'amount' => 100, 'status' => 'sent', 'client_id' => $client->id, 'exchange_rate_date' => now(), 'due_date' => now()]);

        $view = view('erp::invoices.pdf', ['invoice' => $invoice, 'lang' => 'en'])->render();
        $this->assertStringContainsString('Powered by', $view);
    }

    public function test_invoice_pdf_hides_branding_with_white_label_addon()
    {
        $owner = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $owner->id, 'name' => 'Test Tenant', 'status' => 'active']);
        
        \App\Models\UserSubscription::create([
            'user_id' => $owner->id,
            'object' => 'erp-white-label',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $client = \Modules\ERP\Models\TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'client@test.com', 'currency_id' => 1]);
        $invoice = Invoice::create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-002', 'amount' => 100, 'status' => 'sent', 'client_id' => $client->id, 'exchange_rate_date' => now(), 'due_date' => now()]);

        $view = view('erp::invoices.pdf', ['invoice' => $invoice, 'lang' => 'en'])->render();
        $this->assertStringNotContainsString('Powered by', $view);
    }
}
