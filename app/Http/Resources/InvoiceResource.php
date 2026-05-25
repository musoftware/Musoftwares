<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->enc_id(),
            'user' => $this->whenLoaded('user', fn () => $this->user->only('id', 'name', 'email', 'address', 'phone_number')),
            'project' => $this->whenLoaded('project', fn () => ['id' => $this->project->id, 'project_name' => $this->project->project_name]),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'amount' => $this->total(),
            'sub_total' => $this->sub_total(),
            'discount' => $this->discount,
            'tax' => $this->tax(),
            'paid_amount' => $this->paid,
            'currency' => $this->currency,
            'business_amount' => $this->business_total(),
            'business_currency' => \App\Models\AdminSettings::GetValue('business_currency', 2),
            'status' => $this->status,
            'job_status' => $this->job_status ?? 'pending',
            'created_at' => $this->created_at,
            'due_date' => $this->due_date ?? null,
            'total_timer_str' => method_exists($this->resource, 'total_timer_str') ? $this->total_timer_str() : '00:00:00',
            'revenue' => method_exists($this->resource, 'revenue') ? $this->revenue() : ($this->total() - $this->cost),
            'cost' => $this->cost ?? 0,
            
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
