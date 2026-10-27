<?php

namespace Tests\Feature;

use App\Services\PortfolioData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HowWeWorkExamplesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_example_links_to_a_real_portfolio_project_in_both_languages(): void
    {
        foreach (['en', 'ar'] as $locale) {
            $examples = trans('custom_systems.examples', [], $locale);
            $this->assertGreaterThanOrEqual(12, count($examples));

            foreach ($examples as $example) {
                if (! empty($example['project'])) {
                    $this->assertNotNull(PortfolioData::find($example['project']), "{$locale}: {$example['project']} is not in the portfolio");
                }
            }
        }

        $this->assertSame(
            array_column(trans('custom_systems.examples', [], 'en'), 'project'),
            array_column(trans('custom_systems.examples', [], 'ar'), 'project'),
        );
    }

    public function test_page_shows_the_project_links(): void
    {
        $this->get('/how-we-work')->assertOk()
            ->assertSee(route('portfolio.show', 'stock-manager'), false)
            ->assertSee(route('portfolio.show', 'trenz-whatscrm'), false)
            ->assertSee(__('custom_systems.examples_all'));
    }
}
