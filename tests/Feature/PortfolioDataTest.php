<?php

namespace Tests\Feature;

use App\Services\PortfolioData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The portfolio says only what the real screenshots show: one picture per project, no invented numbers. */
class PortfolioDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_project_has_its_own_existing_picture(): void
    {
        $seen = [];
        foreach (PortfolioData::all() as $slug => $project) {
            $file = public_path(ltrim($project['img'], '/'));
            $this->assertFileExists($file, $slug);

            $hash = md5_file($file);
            $this->assertArrayNotHasKey($hash, $seen, "{$slug} uses the same picture as ".($seen[$hash] ?? ''));
            $seen[$hash] = $slug;

            foreach ($project['gallery'] ?? [] as $shot) {
                $this->assertFileExists(public_path(ltrim($shot, '/')), "{$slug} gallery");
            }
        }
    }

    public function test_no_project_claims_speeds_or_percentages(): void
    {
        foreach (PortfolioData::all() as $slug => $project) {
            $text = json_encode($project, JSON_UNESCAPED_UNICODE);
            $this->assertDoesNotMatchRegularExpression('/\d+(\.\d+)?\s*(%|ms\b)|<\s*\d|\d+k\+|sub-second|millions of/iu', $text, $slug);
        }
    }

    public function test_project_pages_open_and_merged_projects_redirect(): void
    {
        foreach (array_keys(PortfolioData::all()) as $slug) {
            $this->get("/portfolio/{$slug}")->assertOk();
        }

        $this->get('/portfolio/forex-bot')->assertRedirect('/portfolio/chartcash');
        $this->get('/portfolio/forex-app')->assertRedirect('/portfolio/chartcash');
        $this->get('/portfolio/wallet-app')->assertRedirect('/portfolio/qcoin-wallet');
        $this->get('/portfolio/telecom-system')->assertRedirect('/portfolio/vodafone-crm');
    }

    public function test_project_page_shows_the_corrected_text(): void
    {
        $this->get('/portfolio/mini-fatora?lang=en')->assertOk()
            ->assertSee('affiliate platform')
            ->assertDontSee('invoice', false);
    }
}
