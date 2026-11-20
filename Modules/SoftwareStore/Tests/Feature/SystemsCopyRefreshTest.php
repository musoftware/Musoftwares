<?php

namespace Modules\SoftwareStore\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\SoftwareStore\Database\Seeders\SoftwareProductSeeder;
use Modules\SoftwareStore\Database\Seeders\SystemsCatalog;
use Modules\SoftwareStore\Database\Seeders\SystemsCopyBaseline;
use Modules\SoftwareStore\Models\SoftwareProduct;
use Modules\SoftwareStore\Services\SystemsCopyRefresh;
use Tests\TestCase;

class SystemsCopyRefreshTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_FAQ = ['title' => 'Common questions', 'items' => [['q' => 'Does it take online payments inside the system?', 'a' => 'No. Payments inside the system are cash only.']]];

    /** The workshop as production has it: old seeded FAQ and tagline, a headline the admin rewrote, a block never seeded, and a price, visibility and pictures set in the admin. */
    private function seedOldWorkshop(): SoftwareProduct
    {
        $this->seed(SoftwareProductSeeder::class);
        $product = SoftwareProduct::where('slug', 'workshop-system')->firstOrFail();
        $landing = $product->landing;
        $landing['en']['faq'] = self::OLD_FAQ;
        $landing['en']['headline'] = 'Our own headline, typed in the admin';
        unset($landing['ar']['cta']);
        $product->update([
            'landing' => $landing, 'tagline_en' => 'Old tagline', 'price' => 7777, 'is_active' => true,
            'gallery' => ['software-products/workshop-system/shot-01.png'], 'plans' => [['key' => 'source', 'name' => 'Mine', 'price' => 6000]],
        ]);

        return $product->fresh();
    }

    private function refresh(): SystemsCopyRefresh
    {
        $baseline = ['workshop-system' => [
            'en.faq' => [SystemsCopyBaseline::fingerprint(self::OLD_FAQ)],
            'tagline_en' => [SystemsCopyBaseline::fingerprint('Old tagline')],
            'en.headline' => [SystemsCopyBaseline::fingerprint('The headline we seeded before')],
        ]];

        return new SystemsCopyRefresh($baseline);
    }

    private function seeded(string $slug): array
    {
        return collect(SystemsCatalog::programs())->firstWhere('slug', $slug);
    }

    public function test_the_dry_run_lists_the_changes_and_saves_nothing(): void
    {
        $before = $this->seedOldWorkshop();

        $plan = $this->refresh()->plan();

        $this->assertSame([
            'tagline_en' => SystemsCopyRefresh::UPDATE, 'ar.cta' => SystemsCopyRefresh::ADD,
            'en.headline' => SystemsCopyRefresh::KEEP, 'en.faq' => SystemsCopyRefresh::UPDATE,
        ], $plan['workshop-system']);
        $this->assertSame($before->landing, $before->fresh()->landing);
        $this->assertSame('Old tagline', $before->fresh()->tagline_en);
    }

    public function test_apply_replaces_unchanged_copy_keeps_admin_text_and_leaves_the_rest_alone(): void
    {
        $before = $this->seedOldWorkshop();
        $gridReader = SoftwareProduct::where('slug', 'gridreader')->firstOrFail();
        $gridReader->update(['tagline_en' => 'Old tagline']);
        $seeded = $this->seeded('workshop-system');

        $this->refresh()->apply();

        $after = $before->fresh();
        $this->assertSame($seeded['landing']['en']['faq'], $after->landing['en']['faq']);
        $this->assertSame($seeded['landing']['ar']['cta'], $after->landing['ar']['cta']);
        $this->assertSame($seeded['tagline_en'], $after->tagline_en);
        $this->assertSame('Our own headline, typed in the admin', $after->landing['en']['headline']);
        $this->assertSame(array_diff_key($before->landing['ar'], ['cta' => 1]), array_diff_key($after->landing['ar'], ['cta' => 1]));

        $this->assertSame('7777.00', $after->price);
        $this->assertTrue($after->is_active);
        $this->assertSame($before->gallery, $after->gallery);
        $this->assertSame($before->plans, $after->plans);
        $this->assertSame($before->theme, $after->theme);
        $this->assertSame('Old tagline', $gridReader->fresh()->tagline_en);
        $this->assertSame(['en.headline' => SystemsCopyRefresh::KEEP], $this->refresh()->plan()['workshop-system'], 'a second run only reports the admin text');
    }

    public function test_the_command_is_a_dry_run_until_apply_is_given(): void
    {
        $product = $this->seedOldWorkshop();
        $this->app->instance(SystemsCopyRefresh::class, $this->refresh());

        $this->assertSame(0, Artisan::call('store:refresh-systems-copy'));
        $output = Artisan::output();
        $this->assertStringContainsString('en.faq: update', $output);
        $this->assertStringContainsString('en.headline: kept (edited in the admin)', $output);
        $this->assertStringContainsString('Dry run: nothing was saved', $output);
        $this->assertSame(self::OLD_FAQ, $product->fresh()->landing['en']['faq']);

        $this->assertSame(0, Artisan::call('store:refresh-systems-copy', ['--apply' => true]));
        $this->assertStringContainsString('Saved.', Artisan::output());
        $this->assertNotSame(self::OLD_FAQ, $product->fresh()->landing['en']['faq']);
    }

    public function test_a_freshly_seeded_store_has_nothing_to_refresh(): void
    {
        $this->seed(SoftwareProductSeeder::class);

        $this->assertSame([], (new SystemsCopyRefresh)->plan());
    }

    public function test_the_baseline_covers_the_15_systems_and_matches_blocks_that_did_not_change(): void
    {
        $baseline = SystemsCopyBaseline::fingerprints();

        $this->assertCount(15, $baseline);
        foreach ($baseline as $slug => $keys) {
            $program = $this->seeded($slug);
            $this->assertNotNull($program, "{$slug} is still in the catalog");
            $video = SoftwareProductSeeder::withExtras($slug, $program['landing'])['en']['video'];
            $this->assertContains(SystemsCopyBaseline::fingerprint($video), $keys['en.video'], 'the old and new fingerprints are made the same way');
            $this->assertNotContains(SystemsCopyBaseline::fingerprint($program['landing']['en']['plans']), $keys['en.plans'], "{$slug}: the cash-only line is gone");
        }
    }
}
