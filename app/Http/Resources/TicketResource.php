<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Ticket
 */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tier = $this->user?->loyaltyTier;
        $tierSlug = strtolower($tier?->slug ?? $this->user?->tier ?? 'standard');
        $tierName = $tier?->name ?? ucfirst($tierSlug);
        $tierColor = $tier?->badge_color ?? 'slate';

        $slaMinutes = match ($tierSlug) {
            'obsidian' => 15,
            'diamond' => 30,
            'ruby', 'emerald' => 60,
            'platinum' => 120,
            'gold' => 240,
            'silver' => 480,
            default => 1440,
        };

        $createdAt = $this->created_at ? \Illuminate\Support\Carbon::parse($this->created_at) : now();
        $slaDueAt = $createdAt->copy()->addMinutes($slaMinutes);
        $isClosed = in_array($this->ticket_status, ['closed', 'resolved']);
        $isOverdue = ! $isClosed && now()->greaterThan($slaDueAt);
        $isVip = in_array($tierSlug, ['obsidian', 'diamond', 'ruby', 'emerald', 'platinum']) || ($this->priority_score ?? 0) >= 50;

        return [
            'id' => $this->id,
            'ticket_subject' => $this->ticket_subject,
            'ticket_message' => $this->ticket_message,
            'ticket_status' => $this->ticket_status,
            'priority' => $this->priority,
            'rate' => $this->rate,
            'status_text' => $this->status_text(),
            'status_color' => $this->status_color(),
            'priority_badge' => $this->priority_badge(),
            'priority_text' => $this->priority_text(),
            'display_name' => $this->getDisplayName(),
            'display_email' => $this->getDisplayEmail(),
            'is_urgent' => $this->is_urgent(),
            'assigned_employee_id' => $this->assigned_employee_id,
            'price' => $this->price !== null ? (float) $this->price : null,
            'currency_id' => $this->currency_id,
            'currency_symbol' => $this->currency?->symbol ?? 'EGP',
            'pricing_status' => $this->pricing_status ?? 'pending',
            'pricing_notes' => $this->pricing_notes,
            'quoted_at' => $this->quoted_at instanceof \DateTimeInterface
                ? $this->quoted_at->toIso8601String()
                : ($this->quoted_at ? \Illuminate\Support\Carbon::parse($this->quoted_at)->toIso8601String() : null),
            'priority_score' => (int) ($this->priority_score ?? 0),
            'sla_target_minutes' => $slaMinutes,
            'sla_due_at' => $slaDueAt->toIso8601String(),
            'is_overdue' => $isOverdue,
            'is_vip' => $isVip,
            'client_tier' => [
                'slug' => $tierSlug,
                'name' => $tierName,
                'color' => $tierColor,
                'badge_svg' => $tier?->badge_svg,
            ],

            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->project_name ?? $this->project->name,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'tier' => $this->user->tier,
                    'loyalty_points_balance' => $this->user->loyalty_points_balance,
                    'loyalty_tier' => $this->user->loyaltyTier ? [
                        'name' => $this->user->loyaltyTier->name,
                        'slug' => $this->user->loyaltyTier->slug,
                        'badge_color' => $this->user->loyaltyTier->badge_color,
                        'badge_svg' => $this->user->loyaltyTier->badge_svg,
                    ] : null,
                ];
            }),

            'conversation' => $this->whenLoaded('conversation', function () {
                $conv = $this->conversation;

                return [
                    'id' => $conv->id,
                    'messages' => $conv->relationLoaded('messages')
                        ? MessageResource::collection($conv->messages)->resolve()
                        : [],
                ];
            }),

            'closed_at' => $this->closed_at instanceof \DateTimeInterface
                ? $this->closed_at->toIso8601String()
                : ($this->closed_at ? \Illuminate\Support\Carbon::parse($this->closed_at)->toIso8601String() : null),
            'created_at' => $this->created_at instanceof \DateTimeInterface
                ? $this->created_at->toIso8601String()
                : ($this->created_at ? \Illuminate\Support\Carbon::parse($this->created_at)->toIso8601String() : null),
            'updated_at' => $this->updated_at instanceof \DateTimeInterface
                ? $this->updated_at->toIso8601String()
                : ($this->updated_at ? \Illuminate\Support\Carbon::parse($this->updated_at)->toIso8601String() : null),
        ];
    }
}
