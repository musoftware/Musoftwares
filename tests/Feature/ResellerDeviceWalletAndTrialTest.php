<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\SerialDevice;
use App\Models\SerialDeviceTrialLog;
use App\Models\SerialSoftware;
use App\Models\SerialSoftwarePackage;
use App\Models\SerialSoftwareReseller;
use App\Models\SerialUserDevice;
use App\Models\User;
use App\Services\ResellerDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResellerDeviceWalletAndTrialTest extends TestCase
{
    use RefreshDatabase;

    protected ResellerDeviceService $service;
    protected User $reseller;
    protected SerialSoftware $software;
    protected Currency $usdCurrency;

    protected function setUp(): void
    {
        parent::setUp();
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'software_reseller', 'guard_name' => 'web']);

        $this->service = app(ResellerDeviceService::class);

        $this->usdCurrency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['name' => 'US Dollar', 'rate' => 1.0]
        );

        $this->reseller = User::factory()->create([
            'currency_id' => $this->usdCurrency->id,
            'user_balance' => 0.00,
        ]);
        $this->reseller->assignRole('software_reseller');

        $this->software = SerialSoftware::create([
            'name' => 'Test POS Software ' . uniqid(),
            'is_active' => true,
            'default_status' => 'active',
            'pricing_type' => 'single',
            'requires_payment' => true,
            'price' => 50.00,          // Customer retail price
            'reseller_price' => 25.00, // Reseller wholesale price
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
        ]);

        // Allocate software to reseller
        SerialSoftwareReseller::create([
            'user_id' => $this->reseller->id,
            'serial_software_id' => $this->software->id,
            'max_devices' => 10,
            'status' => SerialSoftwareReseller::STATUS_ACTIVE,
        ]);
    }

    public function test_reseller_cannot_activate_paid_device_without_wallet_balance(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->assignDeviceToCustomer($this->reseller, [
            'serial_software_id' => $this->software->id,
            'device_id' => 'HW-' . uniqid(),
            'customer_name' => 'John Customer',
            'customer_email' => 'john.' . uniqid() . '@example.com',
            'duration_preset' => '1_month',
        ]);
    }

    public function test_reseller_can_activate_paid_device_with_sufficient_balance(): void
    {
        // Top up reseller wallet with $100
        $this->reseller->forceFill(['user_balance' => 100.00])->save();

        $deviceId = 'HW-' . uniqid();
        $userDevice = $this->service->assignDeviceToCustomer($this->reseller, [
            'serial_software_id' => $this->software->id,
            'device_id' => $deviceId,
            'customer_name' => 'John Customer',
            'customer_email' => 'john.' . uniqid() . '@example.com',
            'duration_preset' => '1_month',
        ]);

        $this->assertNotNull($userDevice);
        $this->assertEquals(SerialUserDevice::STATUS_ACTIVE, $userDevice->status);

        // Reseller balance should be reduced by reseller_price ($25.00)
        $this->reseller->refresh();
        $this->assertEquals(75.00, (float) $this->reseller->user_balance);
    }

    public function test_reseller_can_grant_1_day_free_trial_at_zero_cost(): void
    {
        $this->reseller->update(['user_balance' => 0.00]);
        $deviceId = 'HW-TRIAL-' . uniqid();

        $userDevice = $this->service->assignDeviceToCustomer($this->reseller, [
            'serial_software_id' => $this->software->id,
            'device_id' => $deviceId,
            'customer_name' => 'Trial Customer',
            'customer_email' => 'trial.' . uniqid() . '@example.com',
            'duration_preset' => '1_day_trial',
        ]);

        $this->assertNotNull($userDevice);
        $this->assertEquals(SerialUserDevice::STATUS_ACTIVE, $userDevice->status);

        // Balance remains 0.00
        $this->reseller->refresh();
        $this->assertEquals(0.00, (float) $this->reseller->user_balance);

        // Permanent trial log must exist
        $this->assertTrue(SerialDeviceTrialLog::hasUsedTrial($this->software->id, $deviceId));
    }

    public function test_device_cannot_claim_free_trial_again_even_if_deleted(): void
    {
        $deviceId = 'HW-EXPLOIT-' . uniqid();

        // 1. Grant initial free trial
        $userDevice = $this->service->assignDeviceToCustomer($this->reseller, [
            'serial_software_id' => $this->software->id,
            'device_id' => $deviceId,
            'customer_name' => 'Customer A',
            'customer_email' => 'customera.' . uniqid() . '@example.com',
            'duration_preset' => '1_day_trial',
        ]);

        // 2. Reseller deletes/unassigns device
        $this->service->unassignDevice($userDevice);

        // 3. Attempt to assign same device again with free trial -> must throw validation exception
        $this->expectException(ValidationException::class);

        $this->service->assignDeviceToCustomer($this->reseller, [
            'serial_software_id' => $this->software->id,
            'device_id' => $deviceId,
            'customer_name' => 'Customer B',
            'customer_email' => 'customerb.' . uniqid() . '@example.com',
            'duration_preset' => '1_day_trial',
        ]);
    }

    public function test_reseller_cannot_renew_with_free_trial(): void
    {
        $this->reseller->forceFill(['user_balance' => 50.00])->save();
        $deviceId = 'HW-RENEW-' . uniqid();

        $userDevice = $this->service->assignDeviceToCustomer($this->reseller, [
            'serial_software_id' => $this->software->id,
            'device_id' => $deviceId,
            'customer_name' => 'Renew Customer',
            'customer_email' => 'renew.' . uniqid() . '@example.com',
            'duration_preset' => '1_month',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->renewDevice($userDevice, '1_day_trial');
    }

    public function test_reseller_can_renew_device_and_deducts_wallet_balance(): void
    {
        $this->reseller->forceFill(['user_balance' => 100.00])->save();
        $deviceId = 'HW-RENEW-SUCCESS-' . uniqid();

        // 1. Assign 1 month ($25.00 deducted)
        $userDevice = $this->service->assignDeviceToCustomer($this->reseller, [
            'serial_software_id' => $this->software->id,
            'device_id' => $deviceId,
            'customer_name' => 'Renew Happy Customer',
            'customer_email' => 'renewhappy.' . uniqid() . '@example.com',
            'duration_preset' => '1_month',
        ]);

        $this->reseller->refresh();
        $this->assertEquals(75.00, (float) $this->reseller->user_balance);

        $oldExpiry = $userDevice->expires_at;

        // 2. Renew for another 1 month ($25.00 deducted)
        $renewed = $this->service->renewDevice($userDevice, '1_month');

        $this->reseller->refresh();
        $this->assertEquals(50.00, (float) $this->reseller->user_balance);
        $this->assertTrue($renewed->expires_at->gt($oldExpiry));
    }
}

