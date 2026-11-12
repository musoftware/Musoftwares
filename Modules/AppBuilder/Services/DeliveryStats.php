<?php

namespace Modules\AppBuilder\Services;

use Modules\AppBuilder\Models\BuilderDelivery;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;

/**
 * Does the system work without a programmer? Over the last deliveries: how many went out without the owner stepping
 * in, how many the customer kept without asking for a fix, and the real cost against what was paid.
 */
class DeliveryStats
{
    private const LAST = 30;

    /** A fix asked for this soon after a delivery means the delivery was not right. */
    private const FIX_WINDOW_DAYS = 14;

    public function record(BuilderProject $project, float $jobCostUsd): void
    {
        $paidBefore = (float) BuilderDelivery::where('project_id', $project->id)->sum('price_usd');
        BuilderDelivery::create([
            'project_id' => $project->id,
            'version' => $project->version,
            'hands_off' => ! $project->owner_touched,
            'cost_usd' => round($jobCostUsd, 4),
            // what the customer paid since the last delivery is the price of this one
            'price_usd' => round(max(0, (float) $project->paid_total - $paidBefore), 2),
            'delivered_at' => now(),
        ]);
    }

    /** @return array{count: int, hands_off: ?int, accepted: ?int, cost_share: ?int, cost_usd: float, price_usd: float} */
    public function summary(): array
    {
        $deliveries = BuilderDelivery::latest('delivered_at')->limit(self::LAST)->get();
        $count = $deliveries->count();
        $price = (float) $deliveries->sum('price_usd');
        $cost = (float) $deliveries->where('price_usd', '>', 0)->sum('cost_usd');

        return [
            'count' => $count,
            'hands_off' => $count > 0 ? (int) round($deliveries->where('hands_off', true)->count() * 100 / $count) : null,
            'accepted' => $count > 0 ? (int) round($deliveries->reject(fn (BuilderDelivery $d) => $this->fixAsked($d))->count() * 100 / $count) : null,
            'cost_share' => $price > 0 ? (int) round($cost * 100 / $price) : null,
            'cost_usd' => round((float) $deliveries->sum('cost_usd'), 2),
            'price_usd' => round($price, 2),
        ];
    }

    private function fixAsked(BuilderDelivery $delivery): bool
    {
        return BuilderRequest::where('project_id', $delivery->project_id)
            ->where('kind', BuilderRequest::FIX)
            ->whereBetween('created_at', [$delivery->delivered_at, $delivery->delivered_at->copy()->addDays(self::FIX_WINDOW_DAYS)])
            ->exists();
    }
}
