<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Modules\Shop\Services\Content\ProductContentRewriter;

class RewriteProductContent extends Command
{
    protected $signature = 'shop:rewrite-content {--limit=5 : How many products to handle in this run} {--id= : Rewrite this one Angazny product again}';

    protected $description = 'Replace supplier text on imported products with copy written for shoppers';

    public function handle(ProductContentRewriter $rewriter): int
    {
        if ((string) config('services.anthropic.key') === '') {
            $this->info('ANTHROPIC_API_KEY is not set, nothing to do.');

            return self::SUCCESS;
        }

        $id = $this->option('id');
        $this->info(json_encode($rewriter->handle((int) $this->option('limit'), $id ? (int) $id : null)));

        return self::SUCCESS;
    }
}
