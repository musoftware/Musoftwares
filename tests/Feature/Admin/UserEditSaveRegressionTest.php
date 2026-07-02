<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the "Admin user edit save is not working" bug.
 *
 * The reported symptom: when an admin opens /admin/users/{id}/edit and saves
 * the form, the update either does nothing visible, or it silently wipes
 * fields like mobile_1, whatsapp_number, telegram_username, country, city,
 * max_devices, etc.
 *
 * Root cause: AdminUserService::applyFields() assigns fields with
 * `$user->X = $request->input('X')` unconditionally. When the Edit.jsx
 * payload omits a field (or sends it as empty string), `$request->input()`
 * returns null, and the existing column value is overwritten with null.
 *
 * The tests below submit PARTIAL payloads (the way a real Edit form does
 * when an admin only changes one field) and assert that the values that
 * were NOT submitted are preserved. They will fail under the current
 * implementation and pass once the service is fixed.
 */
class UserEditSaveRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
            'name'                 => 'Regression Admin',
            'email'                => 'regression-admin@example.com',
        ]);
        $this->admin->assignRole('admin');

        // Pre-populate the target with realistic data the Edit form would
        // have loaded. These are the values the admin *expects* to survive
        // any partial save.
        $this->target = User::factory()->create([
            'name'                 => 'Original Name',
            'full_name'            => 'Original Full Name',
            'email'                => 'regression-target@example.com',
            'mobile_1'             => '+201001112233',
            'mobile_2'             => '+201001112244',
            'whatsapp_number'      => '+201001112255',
            'telegram_username'    => '@original_tg',
            'country'              => 'EG',
            'city'                 => 'Cairo',
            'max_devices'          => 5,
            'currency_id'          => 2,
            'onboarding_completed' => true,
        ]);
        $this->target->assignRole('client');
    }

    /**
     * Sanity check: a full payload that the existing audit test already
     * covers still works. If THIS fails, the whole admin edit flow is
     * broken — start debugging there first.
     */
    public function test_full_payload_update_still_works(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->target->id}",
            [
                'name'           => 'Renamed',
                'email'          => 'renamed-target@example.com',
                'role'           => 'client',
                'account_status' => 'active',
                'mobile_1'       => '+201009990001',
                'whatsapp_number'=> '+201009990002',
                'telegram_username' => '@new_tg',
                'country'        => 'SA',
                'city'           => 'Riyadh',
                'max_devices'    => 2,
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.show', $this->target->id));

        $fresh = $this->target->fresh();
        $this->assertSame('Renamed', $fresh->name);
        $this->assertSame('+201009990001', $fresh->mobile_1);
        $this->assertSame('+201009990002', $fresh->whatsapp_number);
        $this->assertSame('@new_tg',       $fresh->telegram_username);
        $this->assertSame('SA',            $fresh->country);
        $this->assertSame('Riyadh',        $fresh->city);
        $this->assertSame(2, (int) $fresh->max_devices);
    }

    // ─────────────────────────────────────────────────────────────────────
    // THE BUG: partial payload must NOT wipe existing values.
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Admin only renames the user; mobile_1, whatsapp_number, country,
     * city, telegram_username, max_devices must all survive untouched.
     * Today they are all overwritten with NULL.
     */
    public function test_partial_save_preserves_unsubmitted_contact_fields(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->target->id}",
            [
                'name'           => 'Just Renamed',
                'email'          => 'regression-target@example.com',
                'role'           => 'client',
                'account_status' => 'active',
                // mobile_1, mobile_2, whatsapp_number, telegram_username,
                // country, city, max_devices are deliberately OMITTED
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.show', $this->target->id));

        $fresh = $this->target->fresh();
        $this->assertSame('Just Renamed', $fresh->name, 'name should be updated.');

        // These MUST remain equal to their pre-save values.
        $this->assertSame(
            '+201001112233',
            $fresh->mobile_1,
            'mobile_1 was wiped to NULL because the Edit form omitted it.'
        );
        $this->assertSame(
            '+201001112244',
            $fresh->mobile_2,
            'mobile_2 was wiped to NULL because the Edit form omitted it.'
        );
        $this->assertSame(
            '+201001112255',
            $fresh->whatsapp_number,
            'whatsapp_number was wiped to NULL because the Edit form omitted it.'
        );
        $this->assertSame(
            '@original_tg',
            $fresh->telegram_username,
            'telegram_username was wiped to NULL because the Edit form omitted it.'
        );
        $this->assertSame('EG',    $fresh->country, 'country was wiped to NULL.');
        $this->assertSame('Cairo', $fresh->city,    'city was wiped to NULL.');
        $this->assertSame(
            5,
            (int) $fresh->max_devices,
            'max_devices was wiped to NULL because the Edit form omitted it.'
        );
    }

    /**
     * Edit.jsx submits empty strings for blank fields (React controlled
     * inputs always emit a value). Empty string must NOT wipe the column.
     */
    public function test_empty_string_does_not_overwrite_existing_values(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->target->id}",
            [
                'name'              => 'Just Renamed',
                'email'             => 'regression-target@example.com',
                'role'              => 'client',
                'account_status'    => 'active',
                'mobile_1'          => '',
                'mobile_2'          => '',
                'whatsapp_number'   => '',
                'telegram_username' => '',
                'country'           => '',
                'city'              => '',
                'max_devices'       => '',
            ]
        );

        $response->assertSessionHasNoErrors();

        $fresh = $this->target->fresh();
        $this->assertSame(
            '+201001112233',
            $fresh->mobile_1,
            'mobile_1 was wiped to empty string instead of being preserved.'
        );
        $this->assertSame(
            '+201001112255',
            $fresh->whatsapp_number,
            'whatsapp_number was wiped to empty string instead of being preserved.'
        );
        $this->assertSame(
            '@original_tg',
            $fresh->telegram_username,
            'telegram_username was wiped to empty string instead of being preserved.'
        );
        $this->assertSame('EG',    $fresh->country, 'country was wiped to empty string.');
        $this->assertSame('Cairo', $fresh->city,    'city was wiped to empty string.');
        $this->assertSame(
            5,
            (int) $fresh->max_devices,
            'max_devices was wiped to empty string (and cast to 0).'
        );
    }

    /**
     * The exact symptom reported by the user: "save does not work for admin
     * when editing users." Reproduces by changing ONLY the email and
     * confirming every other column still matches the database BEFORE save.
     */
    public function test_changing_only_email_does_not_lose_other_data(): void
    {
        $before = $this->target->only([
            'name', 'full_name', 'email',
            'mobile_1', 'mobile_2', 'whatsapp_number', 'telegram_username',
            'country', 'city', 'max_devices', 'currency_id',
        ]);

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->target->id}",
            [
                'name'           => $before['name'],
                'email'          => 'only-email-changed@example.com',
                'role'           => 'client',
                'account_status' => 'active',
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $fresh = $this->target->fresh();
        $this->assertSame('only-email-changed@example.com', $fresh->email);

        foreach ($before as $column => $expected) {
            if ($column === 'email') {
                continue; // already asserted
            }
            $actual = $fresh->{$column};
            // Normalise int-cast columns for the comparison.
            $this->assertSame(
                $expected,
                $actual,
                "Column [{$column}] was unexpectedly modified by the save. "
                ."Expected: " . var_export($expected, true)
                ."  Got: "      . var_export($actual, true)
            );
        }
    }

    /**
     * Telegram username is a column added by migration
     * 2026_05_23_150449 — it is a known recent regression vector.
     * Specifically target it: the column is NOT in $fillable, but the
     * service assigns it via $user->telegram_username which bypasses
     * the mass-assignment guard. If the service uses `$request->input()`
     * (returns null when missing), the value will be wiped.
     */
    public function test_telegram_username_survives_partial_save(): void
    {
        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->target->id}",
            [
                'name'           => 'Just Renamed',
                'email'          => 'regression-target@example.com',
                'role'           => 'client',
                'account_status' => 'active',
                // telegram_username omitted
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame('@original_tg', $this->target->fresh()->telegram_username);
    }

    /**
     * max_devices has an integer cast. A null assignment to an int-cast
     * attribute is silently coerced to NULL — which breaks any front-end
     * that assumes the field is a positive integer.
     */
    public function test_max_devices_is_not_nulled_by_partial_save(): void
    {
        $this->assertSame(5, (int) $this->target->max_devices);

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->target->id}",
            [
                'name'           => 'Just Renamed',
                'email'          => 'regression-target@example.com',
                'role'           => 'client',
                'account_status' => 'active',
                // max_devices omitted
            ]
        );

        $response->assertSessionHasNoErrors();
        $fresh = $this->target->fresh();
        $this->assertNotNull(
            $fresh->max_devices,
            'max_devices must not be NULL after a partial save.'
        );
        $this->assertSame(5, (int) $fresh->max_devices);
    }

    /**
     * Currency fallback: the Edit form only sends `currency` when the
     * admin touches the dropdown. The service currently falls back to 2
     * when the column is unset on the model too, which would change a
     * pre-existing USD currency to EGP on save. Verify the existing
     * currency_id is preserved when the request omits the field.
     */
    public function test_currency_id_is_preserved_when_not_submitted(): void
    {
        $this->target->update(['currency_id' => 1]); // USD

        $response = $this->actingAs($this->admin)->put(
            "/admin/users/{$this->target->id}",
            [
                'name'           => 'Just Renamed',
                'email'          => 'regression-target@example.com',
                'role'           => 'client',
                'account_status' => 'active',
                // currency omitted
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame(1, (int) $this->target->fresh()->currency_id);
    }

    /**
     * Show route must still work after the partial save — i.e. the save
     * did not corrupt the model to a state that 500s the next page.
     */
    public function test_show_page_loads_cleanly_after_partial_save(): void
    {
        $this->actingAs($this->admin)->put(
            "/admin/users/{$this->target->id}",
            [
                'name'           => 'Just Renamed',
                'email'          => 'regression-target@example.com',
                'role'           => 'client',
                'account_status' => 'active',
            ]
        )->assertSessionHasNoErrors();

        $response = $this->actingAs($this->admin)
            ->get("/admin/users/{$this->target->id}");

        $response->assertStatus(200);
    }

    /**
     * Edit page must still load after a partial save — guards against
     * the model being corrupted in a way that the next /edit visit
     * explodes (which is what users perceive as "edit stopped working").
     */
    public function test_edit_page_loads_cleanly_after_partial_save(): void
    {
        $this->actingAs($this->admin)->put(
            "/admin/users/{$this->target->id}",
            [
                'name'           => 'Just Renamed',
                'email'          => 'regression-target@example.com',
                'role'           => 'client',
                'account_status' => 'active',
            ]
        )->assertSessionHasNoErrors();

        $response = $this->actingAs($this->admin)
            ->get("/admin/users/{$this->target->id}/edit");

        $response->assertStatus(200);
        $this->assertStringContainsString(
            '@original_tg',
            $response->getContent(),
            'telegram_username disappeared from the Edit page after save.'
        );
        $this->assertStringContainsString(
            '+201001112233',
            $response->getContent(),
            'mobile_1 disappeared from the Edit page after save.'
        );
    }

    /**
     * Bonus regression: the Inertia edit page payload must contain the
     * CURRENTLY persisted values, not the ones from the last save. If
     * the service returns a stale object, this catches it.
     */
    public function test_edit_page_reflects_persisted_state_not_stale_state(): void
    {
        // Persist a fresh value the page MUST reflect.
        $this->target->update(['whatsapp_number' => '+999000111222']);

        $response = $this->actingAs($this->admin)
            ->get("/admin/users/{$this->target->id}/edit");

        $response->assertStatus(200);
        $this->assertStringContainsString('+999000111222', $response->getContent());
    }
}