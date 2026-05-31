<?php

namespace Modules\CRM\Services;

use App\Models\Campaign;
use Modules\CRM\Domains\Automation\Actions\CreateCampaignAction;
use Modules\CRM\Domains\Automation\Actions\UpdateCampaignAction;
use Modules\CRM\Domains\Automation\Actions\DeleteCampaignAction;
use Modules\CRM\Domains\Automation\Actions\ChangeCampaignStatusAction;
use Modules\CRM\Domains\Automation\Jobs\GenerateCampaignAIContentJob;
use Modules\CRM\Domains\Automation\DTOs\CampaignDTO;
use Modules\CRM\Infrastructure\Context\TenantContext;

/**
 * @deprecated Use Domain Actions directly (e.g., CreateCampaignAction, ChangeCampaignStatusAction)
 */
class CampaignService
{
    public function createCampaign(array $data): Campaign
    {
        // Infer TenantContext if possible, or assume it's injected/handled in the Controller
        // For backwards compatibility we construct the DTO:
        $dto = CampaignDTO::fromArray($data);
        return app(CreateCampaignAction::class)->execute($dto);
    }

    public function updateCampaign(Campaign $campaign, array $data): void
    {
        $dto = CampaignDTO::fromArray(array_merge($campaign->toArray(), $data));
        app(UpdateCampaignAction::class)->execute($campaign, $dto);
    }

    public function deleteCampaign(Campaign $campaign): void
    {
        app(DeleteCampaignAction::class)->execute($campaign);
    }

    public function scheduleCampaign(Campaign $campaign): void
    {
        app(ChangeCampaignStatusAction::class)->execute($campaign, 'scheduled');
    }

    public function pauseCampaign(Campaign $campaign): void
    {
        app(ChangeCampaignStatusAction::class)->execute($campaign, 'paused');
    }

    public function resumeCampaign(Campaign $campaign): void
    {
        app(ChangeCampaignStatusAction::class)->execute($campaign, 'active');
    }

    /**
     * @deprecated AI generation is now asynchronous. This method returns an empty array immediately.
     * The actual generation will happen in the background and update the campaign settings.
     * The controller must now dispatch the job instead of relying on this return value.
     */
    public function generateAIContent(string $context, string $tone, string $type): array
    {
        // Since we can't synchronously return the AI content without blocking, 
        // in the deprecated proxy we throw an exception instructing developers to use the Job directly.
        throw new \Exception("Synchronous AI generation is deprecated. Please dispatch GenerateCampaignAIContentJob.");
    }
}
