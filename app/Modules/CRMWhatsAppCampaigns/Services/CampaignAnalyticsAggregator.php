<?php

namespace App\Modules\CRMWhatsAppCampaigns\Services;

use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignAnalytics;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Illuminate\Support\Facades\DB;

class CampaignAnalyticsAggregator
{
    /**
     * Aggregate analytics for a specific campaign.
     */
    public function aggregate(WhatsAppCampaign $campaign): void
    {
        $today = now()->toDateString();
        $hour = now()->hour;

        $stats = $campaign->deliveries()
            ->select([
                DB::raw("COUNT(CASE WHEN status IN ('sent','delivered','read') THEN 1 END) as sent"),
                DB::raw("COUNT(CASE WHEN status IN ('delivered','read') THEN 1 END) as delivered"),
                DB::raw("COUNT(CASE WHEN status = 'read' THEN 1 END) as `read`"),
                DB::raw("COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed"),
                DB::raw("COUNT(CASE WHEN has_replied = 1 THEN 1 END) as replied"),
                DB::raw("COUNT(CASE WHEN has_clicked = 1 THEN 1 END) as clicked"),
                DB::raw("COUNT(CASE WHEN has_opted_out = 1 THEN 1 END) as opted_out"),
            ])
            ->first();

        $sent = $stats->sent ?? 0;
        $delivered = $stats->delivered ?? 0;

        WhatsAppCampaignAnalytics::updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'date'        => $today,
                'hour'        => $hour,
            ],
            [
                'workspace_id'  => $campaign->workspace_id,
                'sent'          => $sent,
                'delivered'     => $delivered,
                'read'          => $stats->read ?? 0,
                'failed'        => $stats->failed ?? 0,
                'replied'       => $stats->replied ?? 0,
                'clicked'       => $stats->clicked ?? 0,
                'opted_out'     => $stats->opted_out ?? 0,
                'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 2) : 0,
                'read_rate'     => $delivered > 0 ? round(($stats->read / $delivered) * 100, 2) : 0,
                'reply_rate'    => $delivered > 0 ? round(($stats->replied / $delivered) * 100, 2) : 0,
                'click_rate'    => $delivered > 0 ? round(($stats->clicked / $delivered) * 100, 2) : 0,
            ]
        );

        // Update denormalized campaign stats
        $campaign->update([
            'sent_count'      => $sent,
            'delivered_count' => $delivered,
            'read_count'      => $stats->read ?? 0,
            'failed_count'    => $stats->failed ?? 0,
            'replied_count'   => $stats->replied ?? 0,
            'clicked_count'   => $stats->clicked ?? 0,
            'opted_out_count' => $stats->opted_out ?? 0,
        ]);
    }

    /**
     * Get campaign analytics overview.
     */
    public function getOverview(int $workspaceId, ?string $period = 'month'): array
    {
        $dateFrom = match ($period) {
            'today' => now()->startOfDay(),
            'week'  => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year'  => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $campaigns = WhatsAppCampaign::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('created_at', '>=', $dateFrom);

        return [
            'total_campaigns'   => (clone $campaigns)->count(),
            'total_sent'        => (clone $campaigns)->sum('sent_count'),
            'total_delivered'   => (clone $campaigns)->sum('delivered_count'),
            'total_read'        => (clone $campaigns)->sum('read_count'),
            'total_failed'      => (clone $campaigns)->sum('failed_count'),
            'total_replied'     => (clone $campaigns)->sum('replied_count'),
            'total_clicked'     => (clone $campaigns)->sum('clicked_count'),
            'avg_delivery_rate' => $this->calculateAvgRate($workspaceId, 'delivery_rate', $dateFrom),
            'avg_read_rate'     => $this->calculateAvgRate($workspaceId, 'read_rate', $dateFrom),
            'avg_reply_rate'    => $this->calculateAvgRate($workspaceId, 'reply_rate', $dateFrom),
        ];
    }

    /**
     * Get detailed analytics for a specific campaign.
     */
    public function getCampaignDetail(WhatsAppCampaign $campaign): array
    {
        $totalRecipients = $campaign->total_recipients ?: 1;

        return [
            'campaign_id'     => $campaign->id,
            'name'            => $campaign->name,
            'status'          => $campaign->status,
            'total_recipients' => $campaign->total_recipients,
            'sent'            => $campaign->sent_count,
            'delivered'       => $campaign->delivered_count,
            'read'            => $campaign->read_count,
            'failed'          => $campaign->failed_count,
            'replied'         => $campaign->replied_count,
            'clicked'         => $campaign->clicked_count,
            'opted_out'       => $campaign->opted_out_count,
            'delivery_rate'   => $campaign->getDeliveryRate(),
            'read_rate'       => $campaign->getReadRate(),
            'reply_rate'      => $campaign->getReplyRate(),
            'progress'        => $campaign->getProgressPercentage(),
            'hourly_trend'    => $this->getHourlyTrend($campaign),
            'status_breakdown' => $this->getStatusBreakdown($campaign),
        ];
    }

    /**
     * Get hourly delivery trend for a campaign.
     */
    protected function getHourlyTrend(WhatsAppCampaign $campaign): array
    {
        return WhatsAppCampaignAnalytics::where('campaign_id', $campaign->id)
            ->orderBy('date')
            ->orderBy('hour')
            ->get(['date', 'hour', 'sent', 'delivered', 'read', 'failed'])
            ->map(fn($a) => [
                'timestamp' => $a->date->format('Y-m-d') . ' ' . str_pad($a->hour, 2, '0', STR_PAD_LEFT) . ':00',
                'sent'      => $a->sent,
                'delivered' => $a->delivered,
                'read'      => $a->read,
                'failed'    => $a->failed,
            ])
            ->all();
    }

    /**
     * Get delivery status breakdown for a campaign.
     */
    protected function getStatusBreakdown(WhatsAppCampaign $campaign): array
    {
        return $campaign->deliveries()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();
    }

    /**
     * Compare two campaigns side by side.
     */
    public function compare(WhatsAppCampaign $campaignA, WhatsAppCampaign $campaignB): array
    {
        return [
            'campaign_a' => $this->getCampaignDetail($campaignA),
            'campaign_b' => $this->getCampaignDetail($campaignB),
        ];
    }

    /**
     * Calculate average rate across campaigns.
     */
    protected function calculateAvgRate(int $workspaceId, string $rateColumn, $dateFrom): float
    {
        $avg = WhatsAppCampaignAnalytics::where('workspace_id', $workspaceId)
            ->where('created_at', '>=', $dateFrom)
            ->avg($rateColumn);

        return round($avg ?? 0, 2);
    }
}
