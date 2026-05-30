<?php

namespace Modules\Freelance\Console;

use Illuminate\Console\Command;
use Modules\Freelance\Models\Job;
use Modules\Freelance\Domains\Finance\Actions\AddPointsAction;
use Illuminate\Support\Facades\DB;
use Modules\Freelance\Domains\Job\States\Open;
use Carbon\Carbon;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

class RefundInactiveJobsCommand extends Command
{
    protected $signature = 'freelance:refund-inactive-jobs';

    protected $description = 'Refunds points for open jobs that have had no proposals for 7 days.';

    public function handle(AddPointsAction $addPointsAction)
    {
        $this->info('Finding inactive open jobs to refund...');

        // Find jobs created more than 7 days ago, still open, with no proposals
        $jobs = Job::whereState('status', Open::class)
            ->where('created_at', '<=', Carbon::now()->subDays(7))
            ->doesntHave('proposals')
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No inactive jobs found.');
            return Command::SUCCESS;
        }

        $refundedCount = 0;

        foreach ($jobs as $job) {
            try {
                DB::transaction(function () use ($job, $addPointsAction) {
                    $job = Job::where('id', $job->id)->lockForUpdate()->first();

                    if (! $job->status->equals(Open::class)) {
                        return; // Someone might have bid exactly right now or cancelled
                    }

                    // Calculate refund amount: base cost (25) + optional min_proposal_points
                    $refundAmount = 25 + $job->min_proposal_points;

                    // Transition state to cancelled
                    $job->status->transitionTo(\Modules\Freelance\Domains\Job\States\Cancelled::class);

                    // Refund points to client
                    $addPointsAction->execute(
                        $job->client_id,
                        $refundAmount,
                        "Automatic refund: Job '{$job->title}' cancelled due to inactivity (7 days with no proposals).",
                        'job',
                        $job->id
                    );
                });

                $this->info("Refunded {$job->title} ({$job->id})");
                $refundedCount++;
            } catch (CouldNotPerformTransition $e) {
                $this->error("Failed to transition job {$job->id}: {$e->getMessage()}");
            } catch (\Exception $e) {
                $this->error("Failed to process job {$job->id}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully refunded {$refundedCount} jobs.");
        return Command::SUCCESS;
    }
}
