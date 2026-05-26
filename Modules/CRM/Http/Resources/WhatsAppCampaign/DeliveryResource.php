<?php
namespace Modules\CRM\Http\Resources\WhatsAppCampaign;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id, 'phone' => $this->phone, 'contact_name' => $this->contact_name,
            'rendered_body' => $this->rendered_body, 'message_type' => $this->message_type,
            'status' => $this->status, 'whatsapp_message_id' => $this->whatsapp_message_id,
            'failed_reason' => $this->failed_reason, 'retry_count' => $this->retry_count,
            'has_replied' => $this->has_replied, 'has_clicked' => $this->has_clicked,
            'queued_at' => $this->queued_at?->toIso8601String(), 'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(), 'read_at' => $this->read_at?->toIso8601String(),
            'account' => $this->whenLoaded('account', fn() => ['id' => $this->account->id, 'phone_number' => $this->account->phone_number]),
        ];
    }
}
