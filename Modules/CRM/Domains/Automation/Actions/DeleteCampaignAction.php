<?php

namespace Modules\CRM\Domains\Automation\Actions;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class DeleteCampaignAction
{
    public function execute(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign) {
            // Additional pre-deletion logic can go here (e.g. canceling scheduled jobs)
            $campaign->delete();
        });
    }
}
