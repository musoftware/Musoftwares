<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Modules\SoftwareStore\Database\Seeders\SystemsCatalogExtra;
use Modules\SoftwareStore\Database\Seeders\SystemsCatalogSupermarket;
use Tests\TestCase;

class SupermarketCatalogTest extends TestCase
{
    public function test_the_supermarket_system_has_the_same_copy_blocks_as_the_other_systems(): void
    {
        $system = SystemsCatalogSupermarket::system();
        $reference = SystemsCatalogExtra::systems()[0];

        $this->assertSame('supermarket-system', $system['slug']);
        foreach (['ar', 'en'] as $lang) {
            foreach (array_keys($reference[$lang]) as $key) {
                $this->assertArrayHasKey($key, $system[$lang], "$lang is missing $key");
            }
            $this->assertCount(3, $system[$lang]['problems']);
            $this->assertCount(3, $system[$lang]['showcase']);
            $this->assertCount(4, $system[$lang]['pillars']);
            $this->assertCount(3, $system[$lang]['metrics']);
        }
        $this->assertFileExists(public_path('images/software/supermarket-system.svg'));
    }

    public function test_the_pages_state_the_limits_and_never_show_demo_logins_or_emojis(): void
    {
        $text = json_encode(SystemsCatalogSupermarket::system(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach (['has not been tested against the real ETA', 'we did not test specific models', 'no first-expiry-first-out', 'fictional', 'no online payment'] as $limit) {
            $this->assertStringContainsString($limit, $text, $limit);
        }
        $this->assertStringNotContainsString('__TESTS__', $text, 'the test count was filled in');
        $this->assertStringNotContainsString('.test', $text);
        $this->assertStringNotContainsString('password', strtolower($text));
        $this->assertSame(0, preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $text), 'no emojis');
    }
}
