<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A project page tells the story in order: what it is, what we built, then technical details.
 * The Arabic page is fully Arabic, and WhatsApp messages speak as the visitor in the page language.
 */
class PortfolioProjectPageTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> decoded WhatsApp messages on the page */
    private function whatsAppMessages(string $html): array
    {
        preg_match_all('~https://wa\.me/201015218548\?text=([^"\'\s<>]+)~', html_entity_decode($html), $found);

        return array_map('rawurldecode', $found[1]);
    }

    public function test_arabic_acel_bay_page_is_arabic_and_keeps_image_link_and_features(): void
    {
        $html = $this->get('/portfolio/acelbay?lang=ar')->assertOk()->getContent();

        foreach (['/images/portfolio/acelbay.png', 'https://acelbay.com/', 'موقع وجهة سياحية', 'اللي عملناه في المشروع ده', 'الأنشطة زي الغطس والسبا والعشا على البحر.'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        foreach (['Resort destination website', 'Architectural', 'مواصفة معمارية', 'Hello Mahmoud', 'database schemas'] as $gone) {
            $this->assertStringNotContainsString($gone, $html);
        }
        foreach ($this->whatsAppMessages($html) as $message) {
            $this->assertStringStartsWith('أهلًا، أنا', $message);
        }
    }

    public function test_english_page_messages_speak_as_the_visitor(): void
    {
        $html = $this->get('/portfolio/acelbay?lang=en')->assertOk()->getContent();

        $messages = $this->whatsAppMessages($html);
        $this->assertContains('Hi, I saw your work on "Acel Bay Red Sea Resort Website" and would like to discuss a similar project.', $messages);
        foreach ($messages as $message) {
            $this->assertStringStartsWith('Hi, I', $message);
        }
        $this->assertStringContainsString('Resort destination website', $html);
    }

    public function test_technical_details_come_after_what_was_built(): void
    {
        $html = $this->get('/portfolio/umkhalid-mixing?lang=en')->assertOk()->getContent();

        $built = strpos($html, 'What we built in this project');
        $tech = strpos($html, 'Technical details');
        $this->assertNotFalse($built);
        $this->assertNotFalse($tech);
        $this->assertGreaterThan($built, $tech);
        $this->assertStringContainsString('WooCommerce', $html);
    }

    public function test_home_links_a_real_client_project_under_the_main_message(): void
    {
        $this->get('/?lang=ar')->assertOk()->assertSeeInOrder(['ابدأ مشروعك', 'من شغلنا لعملاء:', 'موقع منتجع أسيل باي على البحر الأحمر'], false);
    }
}
