<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignMessageDelivered;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs\ProcessCampaignBatchJob;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs\SendCampaignMessageJob;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignAudienceMember;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignDeliveryManager
{
    protected int $accountRotationIndex = 0;

    public function __construct(
        protected WhatsAppProviderInterface $provider,
        protected WhatsAppTemplateRenderer $templateRenderer,
        protected CRMWhatsAppCampaignLimitsService $limitsService,
    ) {}

    /**
     * Generate delivery records for all audience members.
     */
    public function generateDeliveryQueue(WhatsAppCampaign $campaign): int
    {
        $audience = $campaign->audience;
        if (!$audience) {
            throw new \RuntimeException(__('crm.campaign_no_audience'));
        }

        $count = 0;

        $audience->activeMembers()->cursor()->each(function (WhatsAppCampaignAudienceMember $member) use ($campaign, &$count) {
            // Dedup check
            $exists = WhatsAppCampaignDelivery::where('campaign_id', $campaign->id)
                ->where('phone', $member->phone)
                ->whereNull('sequence_step_id')
                ->exists();

            if ($exists) return;

            // Render message
            $body = $campaign->message_body;
            if ($campaign->template) {
                $body = $campaign->template->body;
            }
            $renderedBody = $this->templateRenderer->render($body ?? '', $member->merge_data ?? []);

            WhatsAppCampaignDelivery::create([
                'workspace_id'     => $campaign->workspace_id,
                'campaign_id'      => $campaign->id,
                'phone'            => $member->phone,
                'contact_name'     => $member->name,
                'contactable_type' => $member->contactable_type,
                'contactable_id'   => $member->contactable_id,
                'rendered_body'    => $renderedBody,
                'message_type'     => $campaign->message_type,
                'media_url'        => $campaign->media_url,
                'status'           => 'pending',
                'metadata'         => ['merge_data' => $member->merge_data],
            ]);

            $count++;
        });

        $campaign->update(['total_recipients' => $count]);

        return $count;
    }

    /**
     * Process pending deliveries in batches with throttling.
     */
    public function processBatch(WhatsAppCampaign $campaign): int
    {
        if (!$campaign->isRunning()) {
            return 0;
        }

        // Check usage limits
        if (!$this->limitsService->canUse($campaign->workspace_id, 'monthly_whatsapp_campaign_messages', $campaign->batch_size)) {
            Log::warning("Campaign #{$campaign->id} paused: message limit reached.");
            return 0;
        }

        $deliveries = $campaign->deliveries()
            ->whereIn('status', ['pending', 'queued'])
            ->limit($campaign->batch_size)
            ->get();

        if ($deliveries->isEmpty()) {
            return 0;
        }

        $accounts = $this->getRotationAccounts($campaign);

        foreach ($deliveries as $delivery) {
            $account = $this->getNextAccount($accounts);

            $delivery->update([
                'account_id' => $account->id,
                'status'     => 'queued',
                'queued_at'  => now(),
            ]);

            SendCampaignMessageJob::dispatch($delivery, $account);
        }

        return $deliveries->count();
    }

    /**
     * Send a single campaign message via the WhatsApp provider.
     */
    public function sendMessage(WhatsAppCampaignDelivery $delivery, WhatsAppAccount $account): void
    {
        $campaign = $delivery->campaign;

        if (!$campaign->isRunning()) {
            $delivery->update(['status' => 'skipped']);
            return;
        }

        if (!$account->isConnected()) {
            $delivery->markAsFailed('WhatsApp account is not connected.');
            $this->updateCampaignStats($campaign, 'failed');
            return;
        }

        try {
            $result = match ($delivery->message_type) {
                'text' => $this->provider->sendText($account, $delivery->phone, $delivery->rendered_body),
                'image', 'video', 'audio', 'document' => $this->provider->sendMedia(
                    $account, $delivery->phone, $delivery->media_url, $delivery->message_type, $delivery->rendered_body
                ),
                'template' => $this->provider->sendTemplate(
                    $account, $delivery->phone, $campaign->template?->wa_template_name ?? '', $campaign->template?->wa_template_params ?? []
                ),
                default => $this->provider->sendText($account, $delivery->phone, $delivery->rendered_body),
            };

            $delivery->markAsSent($result['message_id'] ?? '');
            $this->updateCampaignStats($campaign, 'sent');

            // Track usage
            $this->limitsService->increaseUsage($campaign->workspace_id, 'monthly_whatsapp_campaign_messages');

            event(new WhatsAppCampaignMessageDelivered($campaign->workspace_id, $campaign, $delivery));

        } catch (\Exception $e) {
            $delivery->update([
                'status'        => 'failed',
                'failed_reason' => $e->getMessage(),
                'retry_count'   => $delivery->retry_count + 1,
            ]);
            $this->updateCampaignStats($campaign, 'failed');

            Log::error("Campaign message delivery failed: {$e->getMessage()}", [
                'campaign_id' => $campaign->id,
                'delivery_id' => $delivery->id,
                'phone'       => $delivery->phone,
            ]);
        }
    }

    /**
     * Dispatch the next batch for a campaign with throttle delay.
     */
    public function scheduleNextBatch(WhatsAppCampaign $campaign): void
    {
        $remaining = $campaign->deliveries()
            ->whereIn('status', ['pending', 'queued'])
            ->count();

        if ($remaining > 0 && $campaign->isRunning()) {
            ProcessCampaignBatchJob::dispatch($campaign)
                ->delay(now()->addSeconds($campaign->batch_delay_seconds));
        }
    }

    /**
     * Update denormalized campaign stats atomically.
     */
    public function updateCampaignStats(WhatsAppCampaign $campaign, string $type): void
    {
        $column = match ($type) {
            'sent'      => 'sent_count',
            'delivered' => 'delivered_count',
            'read'      => 'read_count',
            'failed'    => 'failed_count',
            'replied'   => 'replied_count',
            'clicked'   => 'clicked_count',
            'opted_out' => 'opted_out_count',
            default     => null,
        };

        if ($column) {
            DB::table('crm_wa_campaigns')
                ->where('id', $campaign->id)
                ->increment($column);
        }
    }

    /**
     * Update delivery status from webhook.
     */
    public function updateDeliveryStatus(string $whatsappMessageId, string $status): void
    {
        $delivery = WhatsAppCampaignDelivery::where('whatsapp_message_id', $whatsappMessageId)->first();

        if (!$delivery) return;

        $updates = ['status' => $status];

        match ($status) {
            'delivered' => $updates['delivered_at'] = now(),
            'read'      => $updates['read_at'] = now(),
            default     => null,
        };

        $delivery->update($updates);
        $this->updateCampaignStats($delivery->campaign, $status);
    }

    /**
     * Get WhatsApp accounts for rotation.
     */
    protected function getRotationAccounts(WhatsAppCampaign $campaign): array
    {
        $accountIds = $campaign->getRotationAccounts();

        return WhatsAppAccount::withoutGlobalScopes()
            ->whereIn('id', $accountIds)
            ->where('status', 'connected')
            ->get()
            ->all();
    }

    /**
     * Get the next account in rotation.
     */
    protected function getNextAccount(array $accounts): WhatsAppAccount
    {
        if (empty($accounts)) {
            throw new \RuntimeException(__('crm.no_connected_whatsapp_accounts'));
        }

        $account = $accounts[$this->accountRotationIndex % count($accounts)];
        $this->accountRotationIndex++;

        return $account;
    }

    /**
     * Check if campaign has finished all deliveries.
     */
    public function isComplete(WhatsAppCampaign $campaign): bool
    {
        return $campaign->deliveries()
            ->whereIn('status', ['pending', 'queued'])
            ->count() === 0;
    }
}
