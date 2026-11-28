<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\TrialPrices;

/**
 * Moves the AI builder's trial-time quotes to the estimator's price. Without --send it only shows the old and new
 * price of each project; with --send each customer is told in the chat and by email and keeps the old price 48 hours.
 */
class BuilderTrialPrices extends Command
{
    protected $signature = 'builder:trial-prices {--send : tell the customers and start their 48 hours}';

    protected $description = 'Show, or send, the new estimator prices of unpaid AI builder quotes from the trial';

    public function handle(TrialPrices $prices): int
    {
        $rows = [];
        foreach ($prices->candidates() as $project) {
            $rows[] = $this->row($prices, $project);
        }

        $this->table(['#', 'type', 'title', 'old', 'new', 'pages', 'email', 'told'], $rows);
        $this->info(count(array_filter($rows, fn (array $row) => $row[7] === 'yes')).' customers told.');

        return self::SUCCESS;
    }

    private function row(TrialPrices $prices, BuilderProject $project): array
    {
        $old = (float) $project->amount;
        try {
            $new = $prices->newPrice($project);
        } catch (\Throwable $e) {
            $this->warn("#{$project->id}: {$e->getMessage()}");

            return [$project->id, $project->project_type, mb_substr((string) $project->title, 0, 40), $old, 'error', '', '', 'no'];
        }
        $told = $this->option('send') && $prices->announce($project, $new);

        return [$project->id, $project->project_type, mb_substr((string) $project->title, 0, 40), $old, $new, $project->price_scope['pages'] ?? '', ($project->client_email ?: $project->user?->email) ? 'yes' : 'no', $told ? 'yes' : 'no'];
    }
}
