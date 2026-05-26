<?php

namespace App\Modules\CRMWhatsAppInbox\Jobs;

use App\Modules\CRMWhatsAppInbox\Services\WhatsAppMediaService;
use Modules\CRM\Models\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWhatsAppMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [30, 60];
    public $queue = 'whatsapp-media';

    public function __construct(
        public WhatsAppMessage $message,
        public string $providerMediaUrl,
    ) {}

    public function handle(WhatsAppMediaService $mediaService): void
    {
        $result = $mediaService->processIncomingMedia(
            $this->providerMediaUrl,
            $this->message->media_mime_type ?? 'application/octet-stream',
            $this->message->workspace_id
        );

        $this->message->update([
            'media_url'     => $result['url'],
            'media_size'    => $result['size'],
            'thumbnail_url' => $result['thumbnail_url'] ?? null,
            'metadata'      => array_merge($this->message->metadata ?? [], [
                'media_path' => $result['path'],
            ]),
        ]);
    }
}
