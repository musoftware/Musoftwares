<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Modules\Shop\Services\Angazny\AngaznyCatalogueSync;
use Modules\Shop\Services\Angazny\AngaznyClient;

class SyncAngaznyCatalogue extends Command
{
    protected $signature = 'shop:angazny-sync';

    protected $description = 'Import new Angazny products as hidden, and refresh stock and cost on existing ones';

    public function handle(AngaznyClient $client, AngaznyCatalogueSync $sync): int
    {
        if (! $client->isConfigured()) {
            $this->info('ANGAZNY_TOKEN is not set, nothing to do.');

            return self::SUCCESS;
        }

        $report = $sync->handle();
        $this->info(json_encode($report, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
