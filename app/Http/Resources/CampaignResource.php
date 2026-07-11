<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'target_audience' => $this->target_audience,
            'email_subject' => $this->email_subject,
            'email_content' => $this->email_content,
            'whatsapp_content' => $this->whatsapp_content,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'recipients_count' => $this->whenCounted('recipients'),
        ];
    }
}
