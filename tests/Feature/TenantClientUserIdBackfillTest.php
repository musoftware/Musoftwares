<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\Tenant;
use Tests\TestCase;

class TenantClientUserIdBackfillTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_backfill_matches_clients_by_email(): void
    {
        $user = User::factory()->create(['email' => 'match@example.com']);

        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name'    => 'Test Tenant',
            'status'  => 'active',
        ]);

        // Insert a tenant_client with same email but no user_id
        $clientId = DB::table('erp_tenant_clients')->insertGetId([
            'tenant_id'  => $tenant->id,
            'name'       => 'Matched Client',
            'email'      => 'match@example.com',
            'currency'   => 'USD',
            'user_id'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('erp:backfill-client-user-ids');

        $this->assertEquals(0, $exitCode);

        // user_id should now be populated
        $this->assertDatabaseHas('erp_tenant_clients', [
            'id'      => $clientId,
            'user_id' => $user->id,
        ]);
    }

    public function test_backfill_skips_clients_with_no_matching_user(): void
    {
        $user = User::factory()->create();

        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name'    => 'Orphan Tenant',
            'status'  => 'active',
        ]);

        $clientId = DB::table('erp_tenant_clients')->insertGetId([
            'tenant_id'  => $tenant->id,
            'name'       => 'Orphan Client',
            'email'      => 'nobody@nonexistent.com', // no user with this email
            'currency'   => 'USD',
            'user_id'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('erp:backfill-client-user-ids');

        // user_id should still be null
        $this->assertDatabaseHas('erp_tenant_clients', [
            'id'      => $clientId,
            'user_id' => null,
        ]);
    }

    public function test_dry_run_does_not_write_user_id(): void
    {
        $user = User::factory()->create(['email' => 'dryrun@example.com']);

        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name'    => 'Dry Tenant',
            'status'  => 'active',
        ]);

        $clientId = DB::table('erp_tenant_clients')->insertGetId([
            'tenant_id'  => $tenant->id,
            'name'       => 'Dry Client',
            'email'      => 'dryrun@example.com',
            'currency'   => 'USD',
            'user_id'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('erp:backfill-client-user-ids', ['--dry-run' => true]);

        // user_id must remain null in dry-run mode
        $this->assertDatabaseHas('erp_tenant_clients', [
            'id'      => $clientId,
            'user_id' => null,
        ]);
    }

    public function test_skips_clients_that_already_have_user_id(): void
    {
        $user = User::factory()->create(['email' => 'already@example.com']);

        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name'    => 'Already Linked',
            'status'  => 'active',
        ]);

        // Client already has user_id set
        DB::table('erp_tenant_clients')->insertGetId([
            'tenant_id'  => $tenant->id,
            'name'       => 'Already Linked Client',
            'email'      => 'already@example.com',
            'currency'   => 'USD',
            'user_id'    => $user->id, // already set
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Call with another user that has the same email (shouldn't conflict)
        $exitCode = Artisan::call('erp:backfill-client-user-ids');
        $this->assertEquals(0, $exitCode);
        // Command processes 0 records (no output to assert, just no crash)
    }
}
