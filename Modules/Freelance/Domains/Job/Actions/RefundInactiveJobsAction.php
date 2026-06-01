<?php

namespace Modules\Freelance\Domains\Job\Actions;

use Modules\Freelance\Models\Job;
use Illuminate\Support\Facades\DB;
use Modules\Freelance\Domains\Finance\Actions\AddPointsAction;
use Illuminate\Support\Carbon;

class RefundInactiveJobsAction
{
    public function __construct(private AddPointsAction $addPointsAction) {}

    public function execute(): int
    {
        $refundedCount = 0;
        
        // Find open jobs older than 7 days with no proposals
        $jobsToRefund = Job::where('status', 'open')
            ->where('created_at', '<=', Carbon::now()->subDays(7))
            ->doesntHave('proposals')
            ->get();

        foreach ($jobsToRefund as $job) {
            DB::transaction(function () use ($job, &$refundedCount) {
                // Lock the job to prevent concurrent modifications
                $lockedJob = Job::where('id', $job->id)->lockForUpdate()->first();
                
                if ($lockedJob->status->equals(\Modules\Freelance\Domains\Job\States\Open::class) && $lockedJob->proposals()->count() === 0) {
                    $client = $lockedJob->client;
                    
                    if ($client) {
                        // The cost of posting a job is 25 base + the minimum proposal points requirement
                        $totalPointsSpent = 25 + $lockedJob->min_proposal_points;
                        
                        $this->addPointsAction->execute(
                            $client->id,
                            $totalPointsSpent,
                            "Refunded points for inactive job: {$lockedJob->title} (No proposals in 7 days)",
                            'job_inactivity_refund',
                            $lockedJob->id
                        );
                    }
                    
                    $lockedJob->update(['status' => 'cancelled']);
                    $refundedCount++;
                }
            });
        }
        
        return $refundedCount;
    }
}
