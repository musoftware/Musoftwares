<?php

namespace Modules\Tools\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Tools\Models\WaCampaign;
use Modules\Tools\Models\WaQualityEvent;

/**
 * ProcessWaCampaignJob
 * =====================
 * Dispatches a campaign to the Node.js runtime agent via HTTP.
 * Polls for completion and syncs results back to MySQL.
 *
 * Retry strategy: 3 attempts, 60s delay.
 * On failure: marks campaign as 'failed' + records quality event.
 */
class ProcessWaCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 3600; // 1 hour max
    public int $backoff = 60;

    public function __construct(
        private readonly int $campaignId,
        private readonly int $userId,
        private readonly int $runtimePort = 18400,
    ) {}

    public function handle(): void
    {
        $campaign = WaCampaign::findOrFail($this->campaignId);

        if ($campaign->status !== 'running') {
            Log::info("[WaCampaign] Campaign {$this->campaignId} not in running state — skipping");
            return;
        }

        // Build contact list
        $contacts = $campaign->contacts()
            ->select(['wa_contacts.phone', 'wa_contacts.name', 'wa_contacts.company', 'wa_contacts.city'])
            ->get()
            ->map(fn($c) => $c->toArray())
            ->toArray();

        if (empty($contacts)) {
            $campaign->update(['status' => 'failed']);
            return;
        }

        // Dispatch to runtime
        try {
            $response = Http::timeout(30)
                ->post("http://127.0.0.1:{$this->runtimePort}/plugins/whatsapp-sender/run", [
                    'params' => [
                        'action'          => 'send_campaign',
                        'campaign_id'     => (string) $campaign->id,
                        'campaign_name'   => $campaign->name,
                        'contacts'        => $contacts,
                        'message'         => $campaign->message_template,
                        'media_url'       => $campaign->media_url,
                        'account_ids'     => $campaign->account_ids,
                        'account_id'      => $campaign->account_ids[0] ?? 'default',
                        'humanize'        => true,
                        'aggressiveness'  => $campaign->humanize_preset,
                        'max_block_rate'  => $campaign->max_block_rate,
                        'stop_on_block'   => true,
                        'track_delivery'  => true,
                    ],
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("Runtime returned {$response->status()}: {$response->body()}");
            }

            $taskId = $response->json('taskId');
            $campaign->update(['runtime_task_id' => $taskId]);
            Log::info("[WaCampaign] Campaign {$this->campaignId} dispatched — taskId: {$taskId}");

            // Poll for completion
            $this->pollForCompletion($campaign, $taskId);

        } catch (\Throwable $e) {
            Log::error("[WaCampaign] Campaign {$this->campaignId} dispatch failed: {$e->getMessage()}");
            $campaign->update(['status' => 'failed']);
            $this->fail($e);
        }
    }

    private function pollForCompletion(WaCampaign $campaign, string $taskId): void
    {
        $maxAttempts = 720; // 720 × 5s = 1 hour
        $attempt     = 0;

        while ($attempt < $maxAttempts) {
            sleep(5);
            $attempt++;

            try {
                $resp = Http::timeout(10)
                    ->get("http://127.0.0.1:{$this->runtimePort}/tasks/{$taskId}");

                if (!$resp->successful()) continue;

                $data = $resp->json();

                // Sync progress to DB
                if (isset($data['result'])) {
                    $result = $data['result'];
                    $campaign->update([
                        'sent'          => $result['sent']    ?? $campaign->sent,
                        'failed'        => $result['failed']  ?? $campaign->failed,
                        'blocked'       => $result['blocked'] ?? $campaign->blocked,
                        'skipped'       => $result['skipped'] ?? $campaign->skipped,
                        'block_rate'    => isset($result['sent'], $result['blocked']) && $result['sent'] > 0
                                            ? $result['blocked'] / $result['sent'] : 0,
                        'health_score_after' => $result['healthScore'] ?? null,
                    ]);
                }

                if ($data['status'] === 'done') {
                    $campaign->update(['status' => 'completed', 'completed_at' => now()]);
                    Log::info("[WaCampaign] Campaign {$this->campaignId} completed successfully");
                    return;
                }

                if (in_array($data['status'], ['failed', 'stopped'])) {
                    $campaign->update(['status' => 'failed', 'completed_at' => now()]);
                    Log::warn("[WaCampaign] Campaign {$this->campaignId} ended with status: {$data['status']}");
                    return;
                }

            } catch (\Throwable $e) {
                Log::warning("[WaCampaign] Poll error for {$taskId}: {$e->getMessage()}");
            }
        }

        // Timeout
        $campaign->update(['status' => 'failed', 'completed_at' => now()]);
        Log::error("[WaCampaign] Campaign {$this->campaignId} timed out after polling");
    }

    public function failed(\Throwable $exception): void
    {
        WaCampaign::where('id', $this->campaignId)->update(['status' => 'failed', 'completed_at' => now()]);

        WaQualityEvent::create([
            'user_id'    => $this->userId,
            'account_id' => 'system',
            'event_type' => 'campaign_job_failed',
            'payload'    => ['campaign_id' => $this->campaignId, 'error' => $exception->getMessage()],
            'occurred_at' => now(),
        ]);
    }
}
