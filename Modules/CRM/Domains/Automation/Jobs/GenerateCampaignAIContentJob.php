<?php

namespace Modules\CRM\Domains\Automation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Campaign;
use Modules\CRM\Domains\Automation\Actions\GenerateCampaignAIContentAction;
use Modules\CRM\Domains\Automation\Actions\UpdateCampaignAction;
use Modules\CRM\Domains\Automation\DTOs\CampaignDTO;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateCampaignAIContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120; // 2 minutes, as OpenAI can be slow

    public function __construct(
        public int $campaignId,
        public string $context,
        public string $tone,
        public string $type
    ) {}

    public function handle(
        GenerateCampaignAIContentAction $generateAction,
        UpdateCampaignAction $updateAction
    ): void {
        $campaign = Campaign::find($this->campaignId);
        if (!$campaign) {
            return;
        }

        try {
            // Generate content using the synchronous Action
            $content = $generateAction->execute($this->context, $this->tone, $this->type);

            // Merge into settings or update directly based on campaign structure
            $settings = $campaign->settings ?? [];
            $settings['ai_generated'] = $content;

            // Rebuild DTO to update safely
            $dto = CampaignDTO::fromArray([
                'workspace_id'    => $campaign->workspace_id,
                'branch_id'       => $campaign->branch_id,
                'user_id'         => $campaign->user_id,
                'name'            => $campaign->name,
                'type'            => $campaign->type,
                'status'          => $campaign->status,
                'target_audience' => $campaign->target_audience,
                'settings'        => $settings,
            ]);

            $updateAction->execute($campaign, $dto);

        } catch (Throwable $e) {
            Log::error("Failed to generate AI content for Campaign {$this->campaignId}: " . $e->getMessage());
            // Depending on requirements, we might update campaign status to 'ai_failed'
            throw $e;
        }
    }
}
