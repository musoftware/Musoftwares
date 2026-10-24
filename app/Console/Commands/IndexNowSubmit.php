<?php

namespace App\Console\Commands;

use App\Services\IndexNowService;
use Illuminate\Console\Command;

class IndexNowSubmit extends Command
{
    protected $signature = 'seo:indexnow {--force : Send every public page again, not only the changed ones}';

    protected $description = 'Tell IndexNow search engines about new or changed public pages (only what changed).';

    public function handle(IndexNowService $indexNow): int
    {
        if (! $indexNow->enabled() && ! $this->option('force')) {
            $this->info('IndexNow is off. Set the indexnow_enabled setting to 1 to turn it on.');

            return self::SUCCESS;
        }

        $accepted = $indexNow->submitChanged((bool) $this->option('force'));
        $this->info("IndexNow accepted {$accepted} page(s).");

        return self::SUCCESS;
    }
}
