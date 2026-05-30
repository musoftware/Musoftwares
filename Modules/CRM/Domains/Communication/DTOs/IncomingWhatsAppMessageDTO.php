<?php

namespace Modules\CRM\Domains\Communication\DTOs;

class IncomingWhatsAppMessageDTO
{
    public function __construct(
        public string $contactPhone,
        public ?string $contactName,
        public ?string $messageId,
        public string $type,
        public ?string $body,
        public ?string $mediaUrl,
        public ?string $mediaMimeType,
        public ?int $mediaSize,
        public ?string $mediaFilename,
        public ?array $metadata,
    ) {}

    public static function fromWebhookPayload(array $payload): self
    {
        return new self(
            contactPhone: $payload['from'] ?? $payload['contact_phone'] ?? '',
            contactName: $payload['push_name'] ?? $payload['contact_name'] ?? null,
            messageId: $payload['message_id'] ?? null,
            type: $payload['type'] ?? 'text',
            body: $payload['body'] ?? null,
            mediaUrl: $payload['media_url'] ?? null,
            mediaMimeType: $payload['media_mime_type'] ?? null,
            mediaSize: isset($payload['media_size']) ? (int) $payload['media_size'] : null,
            mediaFilename: $payload['media_filename'] ?? null,
            metadata: $payload['metadata'] ?? null,
        );
    }
}
