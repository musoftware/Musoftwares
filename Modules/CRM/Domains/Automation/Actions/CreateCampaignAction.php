<?php

namespace Modules\CRM\Domains\Automation\Actions;

use App\Models\Campaign;
use Modules\CRM\Domains\Automation\DTOs\CampaignDTO;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Infrastructure\Context\TenantContext;

class CreateCampaignAction
{
    public function __construct(protected TenantContext $tenantContext)
    {}

    public function execute(CampaignDTO $dto): Campaign
    {
        return DB::transaction(function () use ($dto) {
            // Automatically ensure the campaign belongs to the active tenant
            $data = $dto->toArray();
            $data['workspace_id'] = $this->tenantContext->getWorkspaceId();
            
            $campaign = Campaign::create($data);

            // Emit Domain Event (e.g., CampaignCreated) if needed
            
            return $campaign;
        });
    }
}
