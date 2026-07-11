<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAuditLog;
use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\User;
use App\Services\CostTransactionAuditService;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCostAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CurrenciesSeeder::class);

        $this->currency = Currency::first();
        AdminSettings::SetValue('business_currency', (string) $this->currency->id);

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_store_cost_writes_created_audit_log(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.costs.store'), [
            'amount' => 150.50,
            'currency_id' => $this->currency->id,
            'reason' => 'Hosting renewal',
        ]);

        $response->assertRedirect(route('admin.costs.index'));

        $cost = CostTransaction::first();
        $this->assertNotNull($cost);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => CostTransactionAuditService::ACTION_CREATED,
            'actor_user_id' => $this->admin->id,
            'target_id' => $cost->id,
            'target_type' => CostTransaction::class,
            'severity' => AdminAuditLog::SEVERITY_INFO,
        ]);

        $log = collect(AdminAuditLog::$logs)
            ->first(fn ($log) => $log->action === CostTransactionAuditService::ACTION_CREATED
                && $log->target_id === $cost->id);

        $this->assertNotNull($log);
        $this->assertSame(150.50, $log->meta['amount']);
        $this->assertSame('Hosting renewal', $log->meta['reason']);
    }

    public function test_update_cost_writes_updated_audit_log_with_changes(): void
    {
        $cost = new CostTransaction;
        $cost->amount = 100;
        $cost->currency_id = $this->currency->id;
        $cost->reason = 'Old reason';
        $cost->save();

        $response = $this->actingAs($this->admin)->put(route('admin.costs.update', $cost->id), [
            'amount' => 250,
            'currency_id' => $this->currency->id,
            'reason' => 'New reason',
        ]);

        $response->assertRedirect(route('admin.costs.index'));

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => CostTransactionAuditService::ACTION_UPDATED,
            'actor_user_id' => $this->admin->id,
            'target_id' => $cost->id,
        ]);

        $log = collect(AdminAuditLog::$logs)
            ->first(fn ($log) => $log->action === CostTransactionAuditService::ACTION_UPDATED
                && $log->target_id === $cost->id);

        $this->assertNotNull($log);
        $this->assertContains('amount', $log->meta['changed']);
        $this->assertContains('reason', $log->meta['changed']);
        $this->assertSame(250, $log->meta['after']['amount']);
        $this->assertSame('New reason', $log->meta['after']['reason']);
    }

    public function test_update_cost_with_no_changes_does_not_write_audit_log(): void
    {
        $cost = new CostTransaction;
        $cost->amount = 100;
        $cost->currency_id = $this->currency->id;
        $cost->reason = 'Stable reason';
        $cost->save();

        $this->actingAs($this->admin)->put(route('admin.costs.update', $cost->id), [
            'amount' => 100,
            'currency_id' => $this->currency->id,
            'reason' => 'Stable reason',
        ])->assertRedirect(route('admin.costs.index'));

        $found = collect(AdminAuditLog::$logs)
            ->contains(fn ($log) => $log->action === CostTransactionAuditService::ACTION_UPDATED
                && $log->target_id === $cost->id);

        $this->assertFalse($found, 'No UPDATED audit log should be written when nothing changed.');
    }

    public function test_delete_cost_writes_deleted_audit_log_with_snapshot(): void
    {
        $cost = new CostTransaction;
        $cost->amount = 75;
        $cost->currency_id = $this->currency->id;
        $cost->reason = 'Office supplies';
        $cost->save();

        $response = $this->actingAs($this->admin)->delete(route('admin.costs.delete', $cost->id));

        $response->assertRedirect(route('admin.costs.index'));

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => CostTransactionAuditService::ACTION_DELETED,
            'actor_user_id' => $this->admin->id,
            'target_id' => $cost->id,
        ]);

        $log = collect(AdminAuditLog::$logs)
            ->first(fn ($log) => $log->action === CostTransactionAuditService::ACTION_DELETED
                && $log->target_id === $cost->id);

        $this->assertNotNull($log);
        $this->assertSame(75, $log->meta['amount']);
        $this->assertSame('Office supplies', $log->meta['reason']);
    }

    public function test_audit_logs_capture_actor_ip_and_user_agent(): void
    {
        $this->actingAs($this->admin)->post(route('admin.costs.store'), [
            'amount' => 10,
            'currency_id' => $this->currency->id,
            'reason' => 'Test cost',
        ])->assertRedirect(route('admin.costs.index'));

        $log = collect(AdminAuditLog::$logs)->first(
            fn ($log) => $log->action === CostTransactionAuditService::ACTION_CREATED
        );

        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, $log->actor_user_id);
        $this->assertNotNull($log->actor_ip);
        $this->assertNotNull($log->actor_user_agent);
    }
}
