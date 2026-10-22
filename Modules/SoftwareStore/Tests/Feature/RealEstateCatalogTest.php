<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Modules\SoftwareStore\Database\Seeders\SystemsCatalogRealEstate;
use PHPUnit\Framework\TestCase;

/** The real estate system's store text must keep its honest limits in both languages. */
class RealEstateCatalogTest extends TestCase
{
    public function test_both_languages_are_complete_and_say_what_the_system_does_not_do(): void
    {
        $system = SystemsCatalogRealEstate::system();

        $this->assertSame('real-estate-system', $system['slug']);
        foreach (['ar', 'en'] as $lang) {
            $text = json_encode($system[$lang], JSON_UNESCAPED_UNICODE);
            $faq = array_column($system[$lang]['faq'], 'a', 'q');

            $this->assertCount(6, $faq, "$lang has the six limit questions");
            $this->assertNotEmpty($system[$lang]['problems']);
            $this->assertNotEmpty($system[$lang]['pillars']);
            $this->assertStringNotContainsString('demo login', strtolower($text));
            $this->assertStringNotContainsString('password', strtolower($text));
        }
    }

    public function test_the_contract_is_described_as_a_draft_and_no_tax_connector_is_claimed(): void
    {
        $en = json_encode(SystemsCatalogRealEstate::system()['en']);
        $ar = json_encode(SystemsCatalogRealEstate::system()['ar'], JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('not legal advice', $en);
        $this->assertStringContainsString('for your lawyer', $en);
        $this->assertStringContainsString('sends nothing to the tax authority', $en);
        $this->assertStringContainsString('ليست استشارة قانونية', $ar);
        $this->assertStringNotContainsString('legally compliant', strtolower($en));
        $this->assertDoesNotMatchRegularExpression('/\\bguarantee/i', $en);
    }
}
