<?php

namespace Tests\Feature;

use App\Support\SiteNav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The floating WhatsApp button used to send "Mahmoud here, send me your project details",
 * as if the owner was writing. The visitor is the one who sends it, so it must speak as the visitor.
 */
class WhatsAppGreetingTest extends TestCase
{
    use RefreshDatabase;

    private const AR = 'أهلًا، أنا مهتم بخدمات Musoftwares وعايز أتكلم معاكم عن مشروع.';

    private const EN = "Hi, I'm interested in Musoftwares services and would like to discuss a project.";

    public function test_chat_link_uses_the_real_number_and_the_visitor_greeting(): void
    {
        $this->assertSame(['201015218548', self::AR], $this->decode(SiteNav::whatsAppChatUrl('ar')));
        $this->assertSame(['201015218548', self::EN], $this->decode(SiteNav::whatsAppChatUrl('en')));
    }

    public function test_every_chat_link_on_the_homepage_speaks_as_the_visitor(): void
    {
        foreach (['ar' => self::AR, 'en' => self::EN] as $lang => $greeting) {
            $html = $this->get('/?lang='.$lang)->assertOk()->getContent();
            preg_match_all('~https://wa\.me/[^"\'\s<>]+~', html_entity_decode($html), $found);
            $messages = array_values(array_filter(array_map(fn (string $url) => $this->decode($url)[1], $found[0])));

            $this->assertGreaterThanOrEqual(3, count($messages), "hero, end button, floating button and phone bar ($lang)");
            foreach ($messages as $message) {
                $this->assertSame($greeting, $message);
            }
        }
    }

    public function test_no_message_presents_the_sender_as_the_owner(): void
    {
        foreach (['ar', 'en'] as $lang) {
            $this->assertStringNotContainsString('محمود هنا', __('general.whatsapp_float_default_msg', [], $lang));
            $this->assertStringNotContainsString('Mahmoud here', __('general.whatsapp_float_default_msg', [], $lang));
        }
        $this->assertSame(self::AR, __('general.whatsapp_float_default_msg', [], 'ar'));
        $this->assertSame(self::EN, __('general.whatsapp_float_default_msg', [], 'en'));
    }

    /** @return array{0: string, 1: string} phone number and the decoded message */
    private function decode(string $url): array
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);

        return [trim($parts['path'] ?? '', '/'), (string) ($query['text'] ?? '')];
    }
}
