<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Modules\Shop\Services\Content\ProductImageArranger;

class ArrangeProductImages extends Command
{
    protected $signature = 'shop:arrange-images {--limit=5 : How many products to handle in this run} {--id= : Arrange this one Angazny product again}';

    protected $description = 'Link each colour to its photo and order the rest of the gallery';

    public function handle(ProductImageArranger $arranger): int
    {
        if ((string) config('services.anthropic.key') === '') {
            $this->info('ANTHROPIC_API_KEY is not set, nothing to do.');

            return self::SUCCESS;
        }

        $id = $this->option('id');
        $this->info(json_encode($arranger->handle((int) $this->option('limit'), $id ? (int) $id : null)));

        return self::SUCCESS;
    }
}
