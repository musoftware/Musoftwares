<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\StoreSeo;
use Tests\TestCase;

/**
 * Titles are what Google shows. They end with the site name in the page language, and a program
 * with a brand name also says what it does in the words people search with.
 */
class SearchTitlesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_program_title_says_what_it_does(): void
    {
        $program = new SoftwareProduct(['slug' => 'maps-leads-pro', 'name_ar' => 'مابس ليدز برو', 'name_en' => 'Maps Leads Pro']);
        $system = new SoftwareProduct(['slug' => 'pharmacy-system', 'name_ar' => 'نظام إدارة الصيدلية', 'name_en' => 'Pharmacy Management System']);

        app()->setLocale('ar');
        $this->assertSame('مابس ليدز برو: برنامج استخراج بيانات العملاء من خرائط جوجل | موسوفت', app(StoreSeo::class)->title($program));
        $this->assertSame('نظام إدارة الصيدلية | موسوفت', app(StoreSeo::class)->title($system));

        app()->setLocale('en');
        $this->assertSame('Maps Leads Pro: Google Maps business data extractor | Musoftware', app(StoreSeo::class)->title($program));
    }

    public function test_a_project_page_title_is_the_project_and_the_site_name(): void
    {
        $this->get('/portfolio/acelbay?lang=ar')->assertOk()
            ->assertSee('<title>موقع منتجع أسيل باي على البحر الأحمر | موسوفت</title>', false);

        $this->get('/portfolio/acelbay?lang=en')->assertOk()
            ->assertSee('<title>Acel Bay Red Sea Resort Website | Musoftware</title>', false)
            ->assertDontSee('Musoftwares</title>', false);
    }
}
