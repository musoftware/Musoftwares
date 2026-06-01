<?php

namespace Tests\Feature;

use App\Events\InvoicePaid;
use App\Events\WithdrawalApproved;
use App\Models\User;
use App\Notifications\InvoicePaidNotification;
use App\Notifications\WithdrawalApprovedNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_invoice_paid_event_dispatches_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'client@example.com']);
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        
        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'user_id' => $user->id,
            'currency' => 'USD'
        ]);

        // Use real models instead of stdClass to avoid ActivityEventListener type errors
        $invoice = new \Modules\ERP\Models\Invoice([
            'invoice_number' => 'INV-001',
            'amount' => 100,
            'amount_currency' => 'USD',
        ]);
        $invoice->id = 1;
        $invoice->setRelation('client', $client);

        event(new InvoicePaid($invoice));

        Notification::assertSentTo(
            $user,
            InvoicePaidNotification::class,
            function ($notification, $channels) use ($invoice) {
                return $notification->invoice->id === $invoice->id;
            }
        );
    }

    public function test_invoice_paid_event_dispatches_notification_via_email_routing(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'tenant@example.com']);
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        
        // No user_id attached
        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Legacy Client',
            'email' => 'legacy@example.com',
            'currency' => 'USD'
        ]);

        $invoice = new \Modules\ERP\Models\Invoice([
            'invoice_number' => 'INV-002',
            'amount' => 200,
            'amount_currency' => 'USD',
        ]);
        $invoice->id = 2;
        $invoice->setRelation('client', $client);

        event(new InvoicePaid($invoice));

        Notification::assertSentOnDemand(
            InvoicePaidNotification::class,
            function ($notification, $channels, $notifiable) use ($client) {
                return $notifiable->routes['mail'] === $client->email;
            }
        );
    }

    public function test_withdrawal_approved_event_dispatches_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'client@example.com']);
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        
        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'user_id' => $user->id,
            'currency' => 'USD'
        ]);

        $withdrawal = new \Modules\ERP\Models\Withdrawal([
            'amount' => 50,
            'currency' => 'USD',
        ]);
        $withdrawal->id = 1;
        $withdrawal->setRelation('client', $client);

        event(new WithdrawalApproved($withdrawal));

        Notification::assertSentTo(
            $user,
            WithdrawalApprovedNotification::class,
            function ($notification, $channels) use ($withdrawal) {
                return $notification->withdrawal->id === $withdrawal->id;
            }
        );
    }
}
