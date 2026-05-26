<?php
namespace Modules\CRM\Http\Resources\WhatsAppCampaign;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'type' => $this->type,
            'body' => $this->body, 'placeholders' => $this->placeholders,
            'media_url' => $this->media_url, 'media_mime_type' => $this->media_mime_type,
            'header_text' => $this->header_text, 'footer_text' => $this->footer_text,
            'buttons' => $this->buttons, 'quick_replies' => $this->quick_replies,
            'cta_url' => $this->cta_url, 'cta_text' => $this->cta_text,
            'wa_template_name' => $this->wa_template_name, 'category' => $this->category,
            'is_approved' => $this->is_approved, 'usage_count' => $this->usage_count,
            'creator' => $this->whenLoaded('creator', fn() => ['id' => $this->creator->id, 'name' => $this->creator->name]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
