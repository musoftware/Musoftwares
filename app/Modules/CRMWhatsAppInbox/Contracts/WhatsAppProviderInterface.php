<?php

namespace App\Modules\CRMWhatsAppInbox\Contracts;

use Modules\CRM\Models\WhatsAppAccount;

/**
 * Provider-agnostic interface for WhatsApp connectivity.
 * 
 * Implementations can wrap Baileys, WhatsApp Cloud API, WAHA, or any other provider.
 * The inbox system never interacts with WhatsApp directly — only through this interface.
 */
interface WhatsAppProviderInterface
{
    /**
     * Initiate connection flow (e.g., generate QR code for pairing).
     *
     * @return array{qr_code?: string, session_id?: string, status: string}
     */
    public function connect(WhatsAppAccount $account): array;

    /**
     * Disconnect and clean up the session.
     */
    public function disconnect(WhatsAppAccount $account): bool;

    /**
     * Get current connection status.
     *
     * @return string disconnected|connecting|connected|banned
     */
    public function getStatus(WhatsAppAccount $account): string;

    /**
     * Send a text message.
     *
     * @return array{message_id: string, status: string}
     */
    public function sendText(WhatsAppAccount $account, string $to, string $body): array;

    /**
     * Send a media message (image, video, audio, document).
     *
     * @return array{message_id: string, status: string}
     */
    public function sendMedia(WhatsAppAccount $account, string $to, string $mediaUrl, string $type, ?string $caption = null): array;

    /**
     * Send a template message (for WhatsApp Business API).
     *
     * @return array{message_id: string, status: string}
     */
    public function sendTemplate(WhatsAppAccount $account, string $to, string $templateName, array $params = []): array;

    /**
     * Get the current QR code for pairing (if in connecting state).
     */
    public function getQrCode(WhatsAppAccount $account): ?string;

    /**
     * Get device/session health information.
     *
     * @return array{battery?: int, plugged?: bool, platform?: string, push_name?: string}
     */
    public function getDeviceInfo(WhatsAppAccount $account): array;
}
