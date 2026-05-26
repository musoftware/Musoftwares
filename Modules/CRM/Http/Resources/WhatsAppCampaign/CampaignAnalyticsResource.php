<?php
namespace Modules\CRM\Http\Resources\WhatsAppCampaign;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignAnalyticsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id, 'date' => $this->date?->format('Y-m-d'), 'hour' => $this->hour,
            'sent' => $this->sent, 'delivered' => $this->delivered, 'read' => $this->read,
            'failed' => $this->failed, 'replied' => $this->replied, 'clicked' => $this->clicked,
            'opted_out' => $this->opted_out,
            'delivery_rate' => $this->delivery_rate, 'read_rate' => $this->read_rate,
            'reply_rate' => $this->reply_rate, 'click_rate' => $this->click_rate,
        ];
    }
}
