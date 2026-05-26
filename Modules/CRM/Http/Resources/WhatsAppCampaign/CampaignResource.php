<?php

namespace Modules\CRM\Http\Resources\WhatsAppCampaign;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'description' => $this->description,
            'type'        => $this->type,
            'status'      => $this->status,
            'template'    => $this->whenLoaded('template', fn() => new TemplateResource($this->template)),
            'audience'    => $this->whenLoaded('audience', fn() => new AudienceResource($this->audience)),
            'account_id'  => $this->account_id,
            'account_rotation' => $this->account_rotation,
            'scheduled_at'     => $this->scheduled_at?->toIso8601String(),
            'started_at'       => $this->started_at?->toIso8601String(),
            'completed_at'     => $this->completed_at?->toIso8601String(),
            'batch_size'          => $this->batch_size,
            'batch_delay_seconds' => $this->batch_delay_seconds,
            'stats' => [
                'total_recipients' => $this->total_recipients,
                'sent'      => $this->sent_count,
                'delivered'  => $this->delivered_count,
                'read'       => $this->read_count,
                'failed'     => $this->failed_count,
                'replied'    => $this->replied_count,
                'clicked'    => $this->clicked_count,
                'opted_out'  => $this->opted_out_count,
            ],
            'rates' => [
                'delivery' => $this->getDeliveryRate(),
                'read'     => $this->getReadRate(),
                'reply'    => $this->getReplyRate(),
                'progress' => $this->getProgressPercentage(),
            ],
            'message_body' => $this->message_body,
            'message_type' => $this->message_type,
            'trigger_event'      => $this->trigger_event,
            'trigger_conditions' => $this->trigger_conditions,
            'creator'    => $this->whenLoaded('creator', fn() => ['id' => $this->creator->id, 'name' => $this->creator->name]),
            'sequences'  => SequenceResource::collection($this->whenLoaded('sequences')),
            'events'     => $this->whenLoaded('events'),
            'metadata'   => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
