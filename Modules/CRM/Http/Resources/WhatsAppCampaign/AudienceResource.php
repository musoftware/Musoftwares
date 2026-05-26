<?php
namespace Modules\CRM\Http\Resources\WhatsAppCampaign;
use Illuminate\Http\Resources\Json\JsonResource;

class AudienceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'description' => $this->description,
            'filters' => $this->filters, 'source_type' => $this->source_type,
            'estimated_size' => $this->estimated_size, 'resolved_size' => $this->resolved_size,
            'last_resolved_at' => $this->last_resolved_at?->toIso8601String(),
            'suppression_rules' => $this->suppression_rules, 'is_dynamic' => $this->is_dynamic,
            'members_count' => $this->whenCounted('members'),
            'creator' => $this->whenLoaded('creator', fn() => ['id' => $this->creator->id, 'name' => $this->creator->name]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
