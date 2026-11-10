<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Modules\Shop\Services\Angazny\AngaznyClient;
use Modules\Shop\Services\Angazny\AngaznyDriveImages;

class ImportAngaznyDriveImages extends Command
{
    protected $signature = 'shop:angazny-drive-images {--limit=8 : How many products to handle in this run}';

    protected $description = 'Add the photos from each Angazny product Drive folder to its gallery';

    public function handle(AngaznyClient $client, AngaznyDriveImages $images): int
    {
        if (! $client->isConfigured()) {
            $this->info('ANGAZNY_TOKEN is not set, nothing to do.');

            return self::SUCCESS;
        }

        $this->info(json_encode($images->handle((int) $this->option('limit'))));

        return self::SUCCESS;
    }
}
