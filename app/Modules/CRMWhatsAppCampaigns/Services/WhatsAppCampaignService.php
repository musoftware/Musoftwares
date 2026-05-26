<?php

namespace App\Modules\CRMWhatsAppCampaigns\Services;

use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCreated;
use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignStarted;
use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCompleted;
use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignFailed;
use App\Modules\CRMWhatsAppCampaigns\Jobs\StartCampaignJob;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignEvent;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WhatsAppCampaignService
{
    public function __construct(
        protected CRMWhatsAppCampaignLimitsService $limitsService,
        protected CampaignAudienceResolver $audienceResolver,
    ) {}

    /**
     * List campaigns with filters and pagination.
     */
    public function list(int $workspaceId, array $filters = []): LengthAwarePaginator
    {
        $query = WhatsAppCampaign::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with(['template', 'audience', 'creator']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Create a new campaign.
     */
    public function create(int $workspaceId, array $data): WhatsAppCampaign
    {
        if (!$this->limitsService->canUse($workspaceId, 'max_active_campaigns')) {
            throw new \App\Modules\CRMWhatsAppInbox\Exceptions\UsageLimitExceededException(
                'Active campaign limit reached.'
            );
        }

        $campaign = DB::transaction(function () use ($workspaceId, $data) {
            $campaign = WhatsAppCampaign::create(array_merge($data, [
                'workspace_id' => $workspaceId,
                'status'       => 'draft',
                'created_by'   => auth()->id(),
            ]));

            WhatsAppCampaignEvent::record($campaign, 'created', 'Campaign created');

            return $campaign;
        });

        event(new WhatsAppCampaignCreated($workspaceId, $campaign));

        return $campaign;
    }

    /**
     * Update a campaign (only in draft/paused state).
     */
    public function update(WhatsAppCampaign $campaign, array $data): WhatsAppCampaign
    {
        if (!in_array($campaign->status, ['draft', 'paused'])) {
            throw new \RuntimeException('Campaign can only be edited in draft or paused state.');
        }

        $campaign->update($data);
        WhatsAppCampaignEvent::record($campaign, 'updated', 'Campaign settings updated');

        return $campaign->fresh();
    }

    /**
     * Start a campaign. Resolves audience, generates delivery queue, begins sending.
     */
    public function start(WhatsAppCampaign $campaign): WhatsAppCampaign
    {
        if (!$campaign->canStart()) {
            throw new \RuntimeException("Campaign cannot be started from status: {$campaign->status}");
        }

        // Validate prerequisites
        $this->validateBeforeStart($campaign);

        $campaign->update([
            'status'     => 'running',
            'started_at' => now(),
        ]);

        WhatsAppCampaignEvent::record($campaign, 'started', 'Campaign started');
        event(new WhatsAppCampaignStarted($campaign->workspace_id, $campaign));

        // Dispatch the orchestration job
        StartCampaignJob::dispatch($campaign);

        return $campaign;
    }

    /**
     * Schedule a campaign for future execution.
     */
    public function schedule(WhatsAppCampaign $campaign, \Carbon\Carbon $scheduledAt): WhatsAppCampaign
    {
        if (!$campaign->isDraft()) {
            throw new \RuntimeException('Only draft campaigns can be scheduled.');
        }

        $this->validateBeforeStart($campaign);

        $campaign->update([
            'status'       => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);

        WhatsAppCampaignEvent::record($campaign, 'scheduled', "Scheduled for {$scheduledAt->toDateTimeString()}");

        return $campaign;
    }

    /**
     * Pause a running campaign.
     */
    public function pause(WhatsAppCampaign $campaign): WhatsAppCampaign
    {
        if (!$campaign->canPause()) {
            throw new \RuntimeException('Campaign is not running.');
        }

        $campaign->update([
            'status'    => 'paused',
            'paused_at' => now(),
        ]);

        WhatsAppCampaignEvent::record($campaign, 'paused', 'Campaign paused');

        return $campaign;
    }

    /**
     * Resume a paused campaign.
     */
    public function resume(WhatsAppCampaign $campaign): WhatsAppCampaign
    {
        if (!$campaign->canResume()) {
            throw new \RuntimeException('Campaign is not paused.');
        }

        $campaign->update([
            'status'    => 'running',
            'paused_at' => null,
        ]);

        WhatsAppCampaignEvent::record($campaign, 'resumed', 'Campaign resumed');

        // Re-dispatch remaining deliveries
        StartCampaignJob::dispatch($campaign);

        return $campaign;
    }

    /**
     * Cancel a campaign.
     */
    public function cancel(WhatsAppCampaign $campaign): WhatsAppCampaign
    {
        if (!$campaign->canCancel()) {
            throw new \RuntimeException('Campaign cannot be cancelled in current state.');
        }

        $campaign->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Mark pending deliveries as skipped
        $campaign->deliveries()->whereIn('status', ['pending', 'queued'])->update(['status' => 'skipped']);

        WhatsAppCampaignEvent::record($campaign, 'cancelled', 'Campaign cancelled');

        return $campaign;
    }

    /**
     * Mark campaign as completed.
     */
    public function complete(WhatsAppCampaign $campaign): WhatsAppCampaign
    {
        $campaign->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        WhatsAppCampaignEvent::record($campaign, 'completed', 'Campaign completed');
        event(new WhatsAppCampaignCompleted($campaign->workspace_id, $campaign));

        return $campaign;
    }

    /**
     * Mark campaign as failed.
     */
    public function fail(WhatsAppCampaign $campaign, string $reason): WhatsAppCampaign
    {
        $campaign->update(['status' => 'failed']);

        WhatsAppCampaignEvent::record($campaign, 'failed', $reason);
        event(new WhatsAppCampaignFailed($campaign->workspace_id, $campaign, $reason));

        return $campaign;
    }

    /**
     * Duplicate a campaign.
     */
    public function duplicate(WhatsAppCampaign $campaign): WhatsAppCampaign
    {
        $new = $campaign->replicate(['uuid', 'status', 'started_at', 'completed_at', 'cancelled_at', 'paused_at',
            'total_recipients', 'sent_count', 'delivered_count', 'read_count',
            'failed_count', 'replied_count', 'clicked_count', 'opted_out_count']);
        $new->name = $campaign->name . ' (Copy)';
        $new->status = 'draft';
        $new->save();

        WhatsAppCampaignEvent::record($new, 'created', "Duplicated from campaign #{$campaign->id}");

        return $new;
    }

    /**
     * Validate campaign has required data before starting.
     */
    protected function validateBeforeStart(WhatsAppCampaign $campaign): void
    {
        $errors = [];

        if (!$campaign->audience_id && !$campaign->trigger_event) {
            $errors[] = 'Campaign must have an audience or a trigger event.';
        }

        if (!$campaign->template_id && !$campaign->message_body) {
            $errors[] = 'Campaign must have a template or inline message.';
        }

        if (!$campaign->account_id && empty($campaign->account_rotation)) {
            $errors[] = 'Campaign must have at least one WhatsApp account assigned.';
        }

        if (!empty($errors)) {
            throw new \RuntimeException('Campaign validation failed: ' . implode(' ', $errors));
        }
    }

    /**
     * Get campaign dashboard summary for a workspace.
     */
    public function getDashboard(int $workspaceId): array
    {
        $campaigns = WhatsAppCampaign::withoutGlobalScopes()->where('workspace_id', $workspaceId);

        return [
            'total'      => (clone $campaigns)->count(),
            'running'    => (clone $campaigns)->where('status', 'running')->count(),
            'scheduled'  => (clone $campaigns)->where('status', 'scheduled')->count(),
            'completed'  => (clone $campaigns)->where('status', 'completed')->count(),
            'draft'      => (clone $campaigns)->where('status', 'draft')->count(),
            'total_sent' => (clone $campaigns)->sum('sent_count'),
            'total_delivered' => (clone $campaigns)->sum('delivered_count'),
            'total_failed'    => (clone $campaigns)->sum('failed_count'),
        ];
    }
}
