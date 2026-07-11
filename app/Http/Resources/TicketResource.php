<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
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
            'needs_attention' => $this->needsAttention(),
            'assigned_employee_id' => $this->assigned_employee_id,

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
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

            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
