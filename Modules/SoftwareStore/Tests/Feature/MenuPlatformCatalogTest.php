<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Modules\SoftwareStore\Database\Seeders\SystemsCatalog;
use Modules\SoftwareStore\Database\Seeders\SystemsCatalogExtra;
use Modules\SoftwareStore\Database\Seeders\SystemsCatalogMenu;
use Tests\TestCase;

class MenuPlatformCatalogTest extends TestCase
{
    public function test_the_menu_platform_has_the_same_copy_blocks_as_the_other_systems(): void
    {
        $menu = SystemsCatalogMenu::system();
        $reference = SystemsCatalogExtra::systems()[0];

        $this->assertSame('qr-menu-platform', $menu['slug']);
        foreach (['ar', 'en'] as $lang) {
            foreach (array_keys($reference[$lang]) as $key) {
                $this->assertArrayHasKey($key, $menu[$lang], "$lang is missing $key");
            }
            $this->assertCount(3, $menu[$lang]['problems']);
            $this->assertCount(3, $menu[$lang]['showcase']);
            $this->assertCount(4, $menu[$lang]['pillars']);
            $this->assertCount(3, $menu[$lang]['metrics']);
        }
        $this->assertFileExists(public_path('images/software/qr-menu-platform.svg'));
    }

    public function test_it_is_sold_as_node_source_code_for_5000_with_its_own_hosting_and_language_answers(): void
    {
        $program = collect(SystemsCatalog::programs())->firstWhere('slug', 'qr-menu-platform');

        $this->assertNotNull($program);
        $this->assertSame(5000, $program['plans'][0]['price']);
        $this->assertSame('Full Node.js source code, install guide, as it is. No modifications. You host it yourself.', $program['plans'][0]['description']);

        $page = json_encode($program['landing']['en']);
        $this->assertStringContainsString('Node.js 20', $page);
        $this->assertStringContainsString('Arabic only', $page);
        $this->assertStringNotContainsString('PHP 8.3', $page);
        $this->assertStringNotContainsString('Laravel', $page);
    }

    public function test_the_laravel_systems_keep_their_plan_text(): void
    {
        $program = collect(SystemsCatalog::programs())->firstWhere('slug', 'nursery-system');

        $this->assertStringContainsString('Full Laravel source code, install guide and demo data', $program['plans'][0]['description']);
        $this->assertStringContainsString('PHP 8.3', json_encode($program['landing']['en']));
    }

    public function test_the_page_never_shows_the_internal_name_or_emojis(): void
    {
        $text = json_encode(SystemsCatalogMenu::system(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertStringNotContainsStringIgnoringCase('sofra', $text);
        $this->assertStringNotContainsString('سفرة', $text);
        $this->assertSame(0, preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $text), 'no emojis');
    }
}
