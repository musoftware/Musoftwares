<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Freelance\Models\Review;

class RevealPendingReviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freelance:reveal-reviews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reveal freelance blind reviews that are older than 14 days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $updatedCount = Review::where('is_visible', false)
            ->where('created_at', '<=', now()->subDays(14))
            ->update(['is_visible' => true]);

        $this->info("Revealed {$updatedCount} pending reviews.");
    }
}
