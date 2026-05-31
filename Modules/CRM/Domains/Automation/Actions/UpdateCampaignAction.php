<?php

namespace Modules\CRM\Domains\Automation\Actions;

use App\Models\Campaign;
use Modules\CRM\Domains\Automation\DTOs\CampaignDTO;
use Illuminate\Support\Facades\DB;

class UpdateCampaignAction
{
    public function execute(Campaign $campaign, CampaignDTO $dto): Campaign
    {
        return DB::transaction(function () use ($campaign, $dto) {
            $data = $dto->toArray();
            
            // Do not override workspace context in updates to avoid moving across workspaces
            unset($data['workspace_id']); 
            
            $campaign->update(array_filter($data, fn($value) => $value !== null));

            return $campaign->refresh();
        });
    }
}
