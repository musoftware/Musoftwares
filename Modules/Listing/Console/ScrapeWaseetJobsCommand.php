<?php

namespace Modules\Listing\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Listing\Services\WaseetScraperService;
use Modules\Listing\Services\AutoUserRegistrationService;

class ScrapeWaseetJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listing:scrape-waseet {--limit=50 : Maximum number of jobs to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated scraping of Waseet Egypt jobs listings, auto-creating user profiles and posting listings';

    protected WaseetScraperService $scraperService;
    protected AutoUserRegistrationService $registrationService;

    /**
     * Create a new command instance.
     */
    public function __construct(WaseetScraperService $scraperService, AutoUserRegistrationService $registrationService)
    {
        parent::__construct();
        $this->scraperService = $scraperService;
        $this->registrationService = $registrationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Starting Waseet Egypt jobs scraper (Limit: {$limit})...");

        // Hardcoded category URLs representing Egyptian job categories on Waseet
        $targetUrls = [
            'https://eg.waseet.net/ar/listing/1804-jobs-available',
            'https://eg.waseet.net/ar/listing/195-jobs',
        ];

        $totalImported = 0;

        foreach ($targetUrls as $url) {
            $this->info("Scraping URL: {$url}");
            try {
                $scrapedAds = $this->scraperService->scrapeJobs($url, $limit - $totalImported);

                if (empty($scrapedAds)) {
                    $this->warn("No listings extracted from: {$url}");
                    continue;
                }

                foreach ($scrapedAds as $ad) {
                    try {
                        $listing = $this->registrationService->registerAndPost($ad);
                        $this->line("Successfully imported: ID {$listing->id} - {$listing->title}");
                        $totalImported++;
                    } catch (\Throwable $e) {
                        $this->error("Error registering listing (Waseet ID: {$ad['waseet_id']}): " . $e->getMessage());
                        Log::error("[ScrapeWaseetJobsCommand] Error importing Waseet ID {$ad['waseet_id']}: " . $e->getMessage(), [
                            'exception' => $e
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $this->error("Critical error scraping URL {$url}: " . $e->getMessage());
                Log::error("[ScrapeWaseetJobsCommand] Critical scraper error: " . $e->getMessage(), [
                    'exception' => $e
                ]);
            }

            if ($totalImported >= $limit) {
                break;
            }
        }

        $this->info("Completed. Total listings imported/updated: {$totalImported}");
        return Command::SUCCESS;
    }
}
