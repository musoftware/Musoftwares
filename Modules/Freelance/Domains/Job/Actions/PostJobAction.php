<?php

namespace Modules\Freelance\Domains\Job\Actions;

use Modules\Freelance\Domains\Job\DTOs\PostJobData;
use Modules\Freelance\Models\Job;
use App\Models\PointTransaction;
use Modules\Freelance\Jobs\NotifyFreelancersForJob;
use Illuminate\Support\Facades\DB;
use App\Models\User;

use Modules\Freelance\Domains\Finance\Actions\DeductPointsAction;

class PostJobAction
{
    public function __construct(private DeductPointsAction $deductPointsAction) {}

    public function execute(PostJobData $data, User $user, int $postCost = 10): Job
    {
        if ($user->points_balance < $postCost) {
            throw new \Exception('Insufficient points to post a job.');
        }

        $job = DB::transaction(function () use ($data, $user, $postCost) {
            $job = Job::create([
                'client_id' => $data->clientId,
                'title' => $data->title,
                'description' => $data->description,
                'budget' => $data->budget,
                'currency_id' => $data->currencyId,
                'type' => $data->type,
                'duration' => $data->duration,
                'status' => 'open',
            ]);

            $job->skills()->syncWithPivotValues($data->skills, ['is_required' => true]);

            $this->deductPointsAction->execute(
                $user->id,
                $postCost,
                "Posted job: {$job->title}",
                'job',
                $job->id
            );

            return $job;
        });

        // Dispatch Notification
        NotifyFreelancersForJob::dispatch($job);

        return $job;
    }
}
