<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceSerial;
use Modules\Marketplace\Services\SoftwareLicenseService;

class SoftwareLicensingTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_serial_key_to_order()
    {
        $category = ServiceCategory::create(['name' => 'Software', 'slug' => 'software']);
        $service = Service::create([
            'seller_id' => User::factory()->create()->id,
            'title' => 'Desktop Automation Bot',
            'category_id' => $category->id,
            'description' => 'Bot software',
            'status' => 'active',
        ]);

        $licenseService = new SoftwareLicenseService();
        $serial = $licenseService->assignSerialToOrder($service->id, 101);

        $this->assertTrue((bool)$serial->is_used);
    }

    public function test_activate_device_and_enforce_quota_limits()
    {
        $category = ServiceCategory::create(['name' => 'Software', 'slug' => 'software']);
        $service = Service::create([
            'seller_id' => User::factory()->create()->id,
            'title' => 'Software App',
            'category_id' => $category->id,
            'description' => 'App',
            'status' => 'active',
        ]);

        $serial = ServiceSerial::create([
            'service_id' => $service->id,
            'serial_code' => 'KEY-TEST-1234',
            'is_used' => true,
        ]);

        $licenseService = new SoftwareLicenseService();

        // Device 1 activation
        $res1 = $licenseService->activateDevice('KEY-TEST-1234', 'HWID-111', '00:11:22:33:44:55');
        $this->assertTrue($res1['activated']);

        // Device 2 activation
        $res2 = $licenseService->activateDevice('KEY-TEST-1234', 'HWID-222', '00:11:22:33:44:66');
        $this->assertTrue($res2['activated']);

        // Device 3 activation
        $res3 = $licenseService->activateDevice('KEY-TEST-1234', 'HWID-333', '00:11:22:33:44:77');
        $this->assertTrue($res3['activated']);

        // Device 4 activation (Exceeds quota limit of 3)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Maximum device activation limit reached (3).");
        $licenseService->activateDevice('KEY-TEST-1234', 'HWID-444', '00:11:22:33:44:88');
    }
}
