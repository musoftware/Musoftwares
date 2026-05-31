<?php

namespace Modules\CRM\Domains\Automation\Actions;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChangeCampaignStatusAction
{
    /**
     * @var array<string>
     */
    protected const VALID_STATUSES = ['draft', 'scheduled', 'active', 'paused', 'completed', 'failed'];

    public function execute(Campaign $campaign, string $status): Campaign
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid campaign status: {$status}");
        }

        return DB::transaction(function () use ($campaign, $status) {
            
            // Hook into specific status transitions if necessary
            // E.g., if changing to 'scheduled', we might want to dispatch a job.
            
            $campaign->update(['status' => $status]);
            return $campaign;
        });
    }
}
