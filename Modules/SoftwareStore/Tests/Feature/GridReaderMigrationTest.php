<?php

namespace Modules\SoftwareStore\Tests\Feature;

use App\Models\SerialSoftware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Tests\TestCase;

class GridReaderMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_audfinder_is_renamed_to_gridreader_on_reseed(): void
    {
        $serial = SerialSoftware::create(['name' => 'AudFinder', 'default_status' => 'inactive']);
        $product = SoftwareProduct::create([
            'slug' => 'audfinder',
            'name_en' => 'AudFinder',
            'name_ar' => 'أود فايندر',
            'serial_software_id' => $serial->id,
            'is_active' => true,
            'price' => 500,
            'currency' => 'EGP',
        ]);

        $this->seed(SoftwareProductSeeder::class);

        $this->assertDatabaseMissing('software_products', ['slug' => 'audfinder']);
        $this->assertDatabaseHas('software_products', [
            'id' => $product->id,
            'slug' => 'gridreader',
            'name_en' => 'GridReader',
            'image_path' => 'images/software/gridreader.png',
        ]);

        $this->assertDatabaseMissing('serial_softwares', ['name' => 'AudFinder']);
        $this->assertDatabaseHas('serial_softwares', ['name' => 'GridReader']);
    }

    public function test_gridreader_service_page_and_checkin_flow(): void
    {
        $serial = SerialSoftware::create(['name' => 'GridReader', 'requires_payment' => true, 'default_status' => 'inactive']);
        $this->seed(SoftwareProductSeeder::class);
        SoftwareProduct::where('slug', 'gridreader')->update(['is_active' => true, 'price' => 1000, 'serial_software_id' => $serial->id]);

        $response = $this->get('/services/gridreader');
        $response->assertOk();
        $response->assertSee('GridReader');
        $response->assertDontSee('AudFinder');

        $checkIn = $this->postJson('/api/serial/device', [
            'program_name' => 'GridReader',
            'device_id' => 'HWID-GRIDREADER-001',
        ]);
        $checkIn->assertOk();
        $this->assertNotNull($checkIn->json('purchase_url'));
        $this->assertStringContainsString('gridreader', $checkIn->json('purchase_url'));
    }
}
