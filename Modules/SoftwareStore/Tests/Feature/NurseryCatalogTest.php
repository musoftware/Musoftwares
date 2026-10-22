<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Modules\SoftwareStore\Database\Seeders\SystemsCatalogExtra;
use Modules\SoftwareStore\Database\Seeders\SystemsCatalogNursery;
use Tests\TestCase;

class NurseryCatalogTest extends TestCase
{
    public function test_the_nursery_system_has_the_same_copy_blocks_as_the_other_systems(): void
    {
        $nursery = SystemsCatalogNursery::system();
        $reference = SystemsCatalogExtra::systems()[0];

        $this->assertSame('nursery-system', $nursery['slug']);
        foreach (['ar', 'en'] as $lang) {
            foreach (array_keys($reference[$lang]) as $key) {
                $this->assertArrayHasKey($key, $nursery[$lang], "$lang is missing $key");
            }
            $this->assertCount(3, $nursery[$lang]['problems']);
            $this->assertCount(3, $nursery[$lang]['showcase']);
            $this->assertCount(4, $nursery[$lang]['pillars']);
            $this->assertCount(3, $nursery[$lang]['metrics']);
        }
        $this->assertFileExists(public_path('images/software/nursery-system.svg'));
    }

    public function test_the_pages_state_the_limits_and_never_show_demo_logins_or_emojis(): void
    {
        $text = json_encode(SystemsCatalogNursery::system(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach (['Nothing is sent automatically', 'no photo sharing', 'does not guarantee that you comply', 'no e-receipt connector', 'fictional'] as $limit) {
            $this->assertStringContainsString($limit, $text, $limit);
        }
        $this->assertStringNotContainsString('.test', $text);
        $this->assertStringNotContainsString('password', strtolower($text));
        $this->assertSame(0, preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $text), 'no emojis');
    }
}
