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

    public function test_seller_can_bulk_add_serials_and_check_inventory()
    {
        $category = ServiceCategory::create(['name' => 'Digital Goods', 'slug' => 'digital-goods']);
        $service = Service::create([
            'seller_id' => User::factory()->create()->id,
            'title' => 'API Access Keys',
            'category_id' => $category->id,
            'description' => 'Vouchers',
            'status' => 'active',
        ]);

        $licenseService = new SoftwareLicenseService();

        $addedCount = $licenseService->addSerialsToService($service->id, [
            'KEY-1001',
            'KEY-1002',
            'KEY-1003',
        ]);

        $this->assertEquals(3, $addedCount);
        $this->assertEquals(3, $licenseService->getAvailableSerialsCount($service->id));

        // Assign one to an order
        $serial = $licenseService->assignSerialToOrder($service->id, 202);
        $this->assertEquals('KEY-1001', $serial->serial_code);
        $this->assertTrue($serial->is_used);

        // Remaining count should be 2
        $this->assertEquals(2, $licenseService->getAvailableSerialsCount($service->id));
    }
}

