<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AdminUserService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Unit tests targeting AdminUserService directly.
 *
 * These isolate the service from the HTTP layer so we can prove that
 * the "fields wiped to NULL on partial save" behaviour lives in the
 * service, not in the FormRequest or the controller.
 */
class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_partial_update_preserves_unsubmitted_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'svc-partial@example.com',
            'mobile_1' => '+201001112233',
            'mobile_2' => '+201001112244',
            'whatsapp_number' => '+201001112255',
            'telegram_username' => '@original_tg',
            'country' => 'EG',
            'city' => 'Cairo',
            'max_devices' => 7,
            'currency_id' => 1,
            'onboarding_completed' => true,
        ]);

        // Request only carries the field the admin actually changed.
        $request = Request::create('/admin/users/'.$user->id, 'PUT', [
            'name' => 'New Name',
            'email' => 'svc-partial@example.com',
            'role' => 'client',
        ]);

        app(AdminUserService::class)->updateFromRequest($user, $request);

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('+201001112233', $user->mobile_1);
        $this->assertSame('+201001112244', $user->mobile_2);
        $this->assertSame('+201001112255', $user->whatsapp_number);
        $this->assertSame('@original_tg', $user->telegram_username);
        $this->assertSame('EG', $user->country);
        $this->assertSame('Cairo', $user->city);
        $this->assertSame(7, (int) $user->max_devices);
        $this->assertSame(1, (int) $user->currency_id);
    }

    public function test_empty_string_does_not_overwrite_existing_values(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'svc-empty@example.com',
            'mobile_1' => '+201001112233',
            'whatsapp_number' => '+201001112255',
            'telegram_username' => '@original_tg',
            'country' => 'EG',
            'city' => 'Cairo',
            'max_devices' => 7,
            'onboarding_completed' => true,
        ]);

        $request = Request::create('/admin/users/'.$user->id, 'PUT', [
            'name' => 'New Name',
            'email' => 'svc-empty@example.com',
            'role' => 'client',
            'mobile_1' => '',
            'whatsapp_number' => '',
            'telegram_username' => '',
            'country' => '',
            'city' => '',
            'max_devices' => '',
        ]);

        app(AdminUserService::class)->updateFromRequest($user, $request);

        $user->refresh();

        $this->assertSame('+201001112233', $user->mobile_1);
        $this->assertSame('+201001112255', $user->whatsapp_number);
        $this->assertSame('@original_tg', $user->telegram_username);
        $this->assertSame('EG', $user->country);
        $this->assertSame('Cairo', $user->city);
        $this->assertSame(7, (int) $user->max_devices);
    }

    public function test_create_does_not_save_when_no_save_is_called_explicitly(): void
    {
        // Bug-shape guard: createFromRequest() returns the user without
        // calling save(). The caller (controller.store) is responsible.
        // We assert here so a future refactor that removes save() from
        // the caller surfaces as a test failure.
        $request = Request::create('/admin/users', 'POST', [
            'name' => 'Brand New',
            'email' => 'svc-create@example.com',
            'role' => 'client',
        ]);

        $user = app(AdminUserService::class)->createFromRequest($request);

        $this->assertSame('Brand New', $user->name);
        $this->assertSame('svc-create@example.com', $user->email);
        // Must not exist in DB until the caller saves it.
        $this->assertDatabaseMissing('users', ['email' => 'svc-create@example.com']);
    }
}
