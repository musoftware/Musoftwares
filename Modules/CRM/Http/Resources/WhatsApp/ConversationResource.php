<?php

namespace Modules\CRM\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'uuid'                 => $this->uuid,
            'contact_phone'        => $this->contact_phone,
            'contact_name'         => $this->contact_name,
            'contact_avatar'       => $this->contact_avatar,
            'type'                 => $this->type,
            'status'               => $this->status,
            'priority'             => $this->priority,
            'is_pinned'            => $this->is_pinned,
            'is_starred'           => $this->is_starred,
            'unread_count'         => $this->unread_count,
            'last_message_at'      => $this->last_message_at?->toIso8601String(),
            'last_message_preview' => $this->last_message_preview,
            'first_response_at'    => $this->first_response_at?->toIso8601String(),
            'resolved_at'          => $this->resolved_at?->toIso8601String(),
            'sla_breached'         => $this->sla_breached,
            'sla_due_at'           => $this->sla_due_at?->toIso8601String(),
            'assigned_department'  => $this->assigned_department,
            'created_at'           => $this->created_at->toIso8601String(),

            // Relations
            'account' => $this->whenLoaded('account', fn() => [
                'id'           => $this->account->id,
                'name'         => $this->account->name,
                'phone_number' => $this->account->phone_number,
            ]),
            'assigned_agent' => $this->whenLoaded('assignedAgent', fn() => [
                'id'     => $this->assignedAgent->id,
                'name'   => $this->assignedAgent->name,
                'email'  => $this->assignedAgent->email,
                'avatar' => $this->assignedAgent->profile_photo_path,
            ]),
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'lead' => $this->whenLoaded('lead', fn() => [
                'id'     => $this->lead->id,
                'name'   => $this->lead->name,
                'email'  => $this->lead->email,
                'status' => $this->lead->status,
                'tags'   => $this->lead->relationLoaded('tags') ? $this->lead->tags->pluck('name') : [],
            ]),
            'sla_policy' => $this->whenLoaded('slaPolicy', fn() => [
                'id'                  => $this->slaPolicy->id,
                'name'                => $this->slaPolicy->name,
                'first_response_time' => $this->slaPolicy->first_response_time,
                'resolution_time'     => $this->slaPolicy->resolution_time,
            ]),
            'participants' => $this->whenLoaded('participants', fn() => $this->participants->map(fn($p) => [
                'user_id'   => $p->user_id,
                'role'      => $p->role,
                'name'      => $p->user?->name,
                'joined_at' => $p->joined_at->toIso8601String(),
            ])),
        ];
    }
}
