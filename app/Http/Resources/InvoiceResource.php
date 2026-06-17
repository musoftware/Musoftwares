<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    use \App\Traits\ConvertsCurrency;

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
                ['projects' => $this->user->relationLoaded('projects') ? $this->user->projects->map(fn($p) => ['id' => $p->id, 'project_name' => $p->project_name])->values()->all() : []]
            )),
            'project' => $this->whenLoaded('project', fn () => ['id' => $this->project->id, 'project_name' => $this->project->project_name]),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'amount' => $this->total(),
            'sub_total' => $this->sub_total(),
            'discount' => $this->discount,
            'tax' => $this->tax(),
            'paid_amount' => $this->paid,
            'currency' => \App\Models\Currency::find($this->currency)?->currency,
            'currency_symbol' => \App\Models\Currency::find($this->currency)?->symbol,
            'business_amount' => $this->business_total(),
            'business_currency' => \App\Models\Currency::find(\App\Models\AdminSettings::GetValue('business_currency', 2))?->currency,
            'status' => $this->status,
            'job_status' => $this->job_status ?? 'pending',
            'created_at' => $this->created_at,
            'due_date' => $this->due_date ?? null,
            'is_published' => $this->is_published ?? 0,
            'archive' => $this->archive ?? 0,
            'scheduled_start_date' => $this->scheduled_start_date,
            'total_timer_str' => method_exists($this->resource, 'total_timer_str') ? $this->total_timer_str() : '00:00:00',
            'revenue' => method_exists($this->resource, 'revenue') ? $this->revenue() : ($this->total() - $this->cost),
            'cost' => $this->cost ?? 0,
            
            // Reference Data
            'currencies' => (object) \App\Models\Currency::all()->pluck('currency', 'id')->toArray(),

            // Pricing Insights
            'fair_price' => $this->calculateFairPrice(),
            'min_price' => $this->calculateMinPrice(),
            'margin_percentage' => $this->calculateMarginPercentage(),

            // Internal Cost Lines
            'cost_lines' => $this->whenLoaded('costLines', fn () => $this->costLines->map(fn($line) => [
                'id' => $line->id,
                'line_type' => $line->line_type,
                'amount' => (float)$line->amount,
                'description' => $line->description,
                'credit_user_id' => $line->credit_user_id,
                'credit_user_name' => $line->creditUser?->name,
                'locked' => $line->isProcessed(),
                'cost_transaction_id' => $line->cost_transaction_id,
            ])),

            // Affiliate Data
            'affiliate_data' => $this->whenLoaded('user', function () {
                if (!$this->user || !$this->user->relationLoaded('ref_user') || !$this->user->ref_user) {
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
                    $actualEarnings = \App\Models\Earning::where('referred_invoice_id', $this->id)
                        ->where('user_id', $affiliate->id)
                        ->get();
                    $actualAmount = $actualEarnings->sum('amount');
                    $earningCurrency = $actualEarnings->first()?->currency ?? $affiliate->currency;
                    $actualAmountStr = \App\Helpers\FinanceHelper::instance()->format_money($actualAmount, $earningCurrency);
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
                
                $estimatedInAffiliateCurrency = \App\Models\CurrenciesExchange::RateToday($estimatedDirect, $this->currency, $affiliate->currency);
                $estimatedStr = \App\Helpers\FinanceHelper::instance()->format_money(round($estimatedInAffiliateCurrency, 2), $affiliate->currency);

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
        $recommended_rate = \App\Models\AdminSettings::GetRecommendedHourlyRate($this->currency ?? 1);
        
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
        $overhead_adjustment = (int) \App\Models\AdminSettings::GetValue('overhead_cost_default', 150);
        return round(($this->cost ?? 0) * ($overhead_adjustment / 100), 2);
    }

    protected function calculateMarginPercentage()
    {
        $current_total = $this->total();
        return $current_total > 0 ? round((($current_total - ($this->cost ?? 0)) / $current_total) * 100, 2) : 0;
    }
}
