<?php

namespace App\Http\Resources;

use App\Helpers\FinanceHelper;
use App\Models\AdminSettings;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Earning;
use App\Traits\ConvertsCurrency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    use ConvertsCurrency;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'invoice_number' => $this->enc_id(),
            'user' => $this->whenLoaded('user', fn () => array_merge(
                $this->user->only('id', 'name', 'email', 'address', 'phone_number'),
                ['projects' => $this->user->relationLoaded('projects') ? $this->user->projects->map(fn ($p) => ['id' => $p->id, 'project_name' => $p->project_name])->values()->all() : []]
            )),
            'project' => $this->whenLoaded('project', fn () => ['id' => $this->project->id, 'project_name' => $this->project->project_name]),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'amount' => $this->total(),
            'sub_total' => $this->sub_total(),
            'discount' => $this->discount,
            'tax' => $this->tax(),
            'paid_amount' => $this->paid,
            'currency' => Currency::find($this->currency)?->currency,
            'currency_symbol' => Currency::find($this->currency)?->symbol,
            'business_amount' => $this->business_total(),
            'business_currency' => Currency::find(AdminSettings::GetValue('business_currency', 2))?->currency,
            'status' => $this->status,
            'job_status' => $this->job_status ?? 'pending',
            'created_at' => $this->created_at,
            'due_date' => $this->due_date ?? null,
            'is_published' => $this->is_published ?? 0,
            'archive' => $this->archive ?? 0,
            'scheduled_start_date' => $this->scheduled_start_date,
            'total_timer_str' => method_exists($this->resource, 'total_timer_str') ? $this->total_timer_str() : '00:00:00',
            'timer_metrics' => $this->calculateTimerMetrics(),
            'revenue' => method_exists($this->resource, 'revenue') ? $this->revenue() : ($this->total() - $this->cost),
            'cost' => $this->cost ?? 0,

            // Reference Data
            'currencies' => (object) Currency::all()->pluck('currency', 'id')->toArray(),

            // Pricing Insights
            'fair_price' => $this->calculateFairPrice(),
            'min_price' => $this->calculateMinPrice(),
            'margin_percentage' => $this->calculateMarginPercentage(),

            // Internal Cost Lines
            'cost_lines' => $this->whenLoaded('costLines', fn () => $this->costLines->map(fn ($line) => [
                'id' => $line->id,
                'line_type' => $line->line_type,
                'amount' => (float) $line->amount,
                'description' => $line->description,
                'credit_user_id' => $line->credit_user_id,
                'credit_user_name' => $line->creditUser?->name,
                'locked' => $line->isProcessed(),
                'cost_transaction_id' => $line->cost_transaction_id,
            ])),

            // Affiliate Data
            'affiliate_data' => $this->whenLoaded('user', function () {
                if (! $this->user || ! $this->user->relationLoaded('ref_user') || ! $this->user->ref_user) {
                    return null;
                }

                $affiliate = $this->user->ref_user;
                $commissionMultiplier = method_exists($affiliate, 'getAffiliateCommissionPercentageForReferredUser')
                    ? $affiliate->getAffiliateCommissionPercentageForReferredUser($this->user)
                    : 1.10;
                $commissionPercent = round(($commissionMultiplier - 1) * 100, 2);
                $addsToTotal = method_exists($affiliate, 'shouldAddCommissionToTotal') && $affiliate->shouldAddCommissionToTotal();

                $isPaid = in_array($this->status, ['paid', 'partially_paid']);
                $actualAmount = 0;
                $actualAmountStr = '';

                if ($isPaid) {
                    $actualEarnings = Earning::where('referred_invoice_id', $this->id)
                        ->where('user_id', $affiliate->id)
                        ->get();
                    $actualAmount = $actualEarnings->sum('amount');
                    $earningCurrency = $actualEarnings->first()?->currency ?? $affiliate->currency;
                    $actualAmountStr = FinanceHelper::instance()->format_money($actualAmount, $earningCurrency);
                }

                $estimatedBase = method_exists($this->resource, 'total_min_cost') ? $this->total_min_cost() : $this->sub_total();
                $estimatedFull = round($estimatedBase - $estimatedBase / $commissionMultiplier, 2);

                $upperRef = $affiliate->relationLoaded('ref_user') ? $affiliate->ref_user : null;
                if ($upperRef) {
                    $rateDecimal = (float) $commissionMultiplier - 1;
                    if ($rateDecimal > 0.10) {
                        $estimatedDirect = round($estimatedBase * ($rateDecimal - 0.01), 2);
                    } else {
                        $estimatedDirect = round($estimatedFull * 0.90, 2);
                    }
                } else {
                    $estimatedDirect = $estimatedFull;
                }

                $estimatedInAffiliateCurrency = CurrenciesExchange::RateToday($estimatedDirect, $this->currency, $affiliate->currency);
                $estimatedStr = FinanceHelper::instance()->format_money(round($estimatedInAffiliateCurrency, 2), $affiliate->currency);

                return [
                    'affiliate_id' => $affiliate->id,
                    'name' => $affiliate->name,
                    'email' => $affiliate->email,
                    'commission_percent' => $commissionPercent,
                    'adds_to_total' => $addsToTotal,
                    'is_paid' => $isPaid,
                    'actual_earned_str' => $actualAmountStr,
                    'estimated_amount_str' => $estimatedStr,
                ];
            }),
        ];
    }

    protected function calculateFairPrice()
    {
        $recommended_rate = AdminSettings::GetRecommendedHourlyRate($this->currency ?? 1);

        $non_timer_total = 0;
        if ($this->relationLoaded('items')) {
            foreach ($this->items as $item) {
                if ($item->item_type !== 'timer') {
                    $non_timer_total += $item->amount; // Not total_amount from front-end, let's just use amount
                }
            }
        }

        $total_hours = method_exists($this->resource, 'total_timer') ? ($this->total_timer() / 3600) : 0;

        return round(($total_hours * $recommended_rate) + $non_timer_total, 2);
    }

    protected function calculateMinPrice()
    {
        $overhead_adjustment = (int) AdminSettings::GetValue('overhead_cost_default', 150);

        return round(($this->cost ?? 0) * ($overhead_adjustment / 100), 2);
    }

    protected function calculateMarginPercentage()
    {
        $current_total = $this->total();

        return $current_total > 0 ? round((($current_total - ($this->cost ?? 0)) / $current_total) * 100, 2) : 0;
    }

    protected function calculateTimerMetrics(): array
    {
        $invoice = $this->resource;
        $currencyId = (int) ($invoice->currency ?? 2);
        $user = $invoice->user;

        $baseRate = FinanceHelper::calculateOverheadHourlyRate();
        $systemBaseRate = CurrenciesExchange::RateToday(
            $baseRate,
            AdminSettings::GetValue('business_currency', 2),
            $currencyId
        );

        $clientRate = 0;
        $isCustomRateEnabled = false;
        if ($user) {
            $isCustomRateEnabled = (bool) ($user->enable_custom_hour_rate ?? false);
            if ((float) ($user->hour_rate ?? 0) > 0) {
                $clientRate = CurrenciesExchange::RateToday(
                    $user->hour_rate,
                    $user->hour_rate_currency_id ?? $user->hour_rate_currency ?? $user->currency_id ?? 1,
                    $currencyId
                );
            }
        }

        $effectiveRate = ($isCustomRateEnabled && $clientRate > 0) ? $clientRate : $systemBaseRate;

        $totalSeconds = 0;
        $billedAmount = 0.0;

        if ($invoice->relationLoaded('items')) {
            foreach ($invoice->items as $item) {
                $timers = $item->relationLoaded('timers') ? $item->timers : $item->timers()->get();
                foreach ($timers as $timer) {
                    $secs = (int) $timer->diff();
                    if ($secs <= 0 && ! empty($timer->date_start) && ! empty($timer->date_end)) {
                        $secs = abs(strtotime($timer->date_end) - strtotime($timer->date_start));
                    }
                    $totalSeconds += $secs;
                    $billedAmount += (float) ($timer->amount ?? 0);
                }
            }
        }

        $fullRealValue = ($totalSeconds / 3600) * $effectiveRate;
        $discountSavings = max(0, $fullRealValue - $billedAmount);

        $totalHours = $totalSeconds > 0 ? ($totalSeconds / 3600) : 0;
        $avgBilledRate = $totalHours > 0 ? ($billedAmount / $totalHours) : 0;
        $avgRealRate = $totalHours > 0 ? ($fullRealValue / $totalHours) : 0;
        $effectiveDiscountPercent = $fullRealValue > 0 ? round((($fullRealValue - $billedAmount) / $fullRealValue) * 100, 1) : 0;

        return [
            'total_seconds' => $totalSeconds,
            'total_hours' => round($totalHours, 2),
            'total_timer_str' => \App\Helpers\TextHelper::secondsToTime($totalSeconds),
            'full_real_value' => round($fullRealValue, 2),
            'full_real_value_str' => FinanceHelper::instance()->format_money($fullRealValue, $currencyId),
            'billed_amount' => round($billedAmount, 2),
            'billed_amount_str' => FinanceHelper::instance()->format_money($billedAmount, $currencyId),
            'discount_savings' => round($discountSavings, 2),
            'discount_savings_str' => FinanceHelper::instance()->format_money($discountSavings, $currencyId),
            'has_discount' => $discountSavings > 0.01,
            'avg_billed_rate' => round($avgBilledRate, 2),
            'avg_billed_rate_str' => FinanceHelper::instance()->format_money($avgBilledRate, $currencyId),
            'avg_real_rate' => round($avgRealRate, 2),
            'avg_real_rate_str' => FinanceHelper::instance()->format_money($avgRealRate, $currencyId),
            'effective_discount_percent' => max(0, $effectiveDiscountPercent),
        ];
    }
}
