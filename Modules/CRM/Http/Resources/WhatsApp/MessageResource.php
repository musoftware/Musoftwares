<?php

namespace Modules\CRM\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'sender_type'      => $this->sender_type,
            'type'             => $this->type,
            'body'             => $this->body,
            'media_url'        => $this->media_url,
            'media_mime_type'  => $this->media_mime_type,
            'media_size'       => $this->media_size,
            'media_filename'   => $this->media_filename,
            'thumbnail_url'    => $this->thumbnail_url,
            'reaction_emoji'   => $this->reaction_emoji,
            'template_name'    => $this->template_name,
            'delivery_status'  => $this->delivery_status,
            'failed_reason'    => $this->failed_reason,
            'is_internal_note' => $this->is_internal_note,
            'is_starred'       => $this->is_starred,
            'mentions'         => $this->mentions,
            'sent_at'          => $this->sent_at?->toIso8601String(),
            'delivered_at'     => $this->delivered_at?->toIso8601String(),
            'read_at'          => $this->read_at?->toIso8601String(),
            'scheduled_at'     => $this->scheduled_at?->toIso8601String(),
            'created_at'       => $this->created_at->toIso8601String(),

            'sender' => $this->whenLoaded('sender', fn() => [
                'id'     => $this->sender->id,
                'name'   => $this->sender->name,
                'avatar' => $this->sender->profile_photo_path,
            ]),
            'quoted_message' => $this->whenLoaded('quotedMessage', fn() => [
                'id'   => $this->quotedMessage->id,
                'body' => $this->quotedMessage->getPreview(80),
                'type' => $this->quotedMessage->type,
            ]),
        ];
    }
}
