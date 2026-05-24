<?php

namespace App\Services;

use App\Models\QrCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /**
     * Generate QR code data based on type and content
     */
    public function generateQrData(string $type, array $data, ?QrCode $qrCode = null): string
    {
        switch ($type) {
            case 'url':
                // Determine website QR subtype (static, dynamic, hosted)
                $urlType = $data['url_type'] ?? 'static';

                // 1. Hosted (Musoftwares redirect for analytics)
                if ($urlType === 'hosted') {
                    // If we already have the model, return permanent tracking URL
                    if ($qrCode) {
                        return route('qr.scan', $qrCode->id);
                    }
                    // For previews (no model yet) return direct URL just for demonstration
                    return $data['url'];
                }

                // 2. Dynamic (Bitly short link)
                if ($urlType === 'dynamic') {
                    // Try to shorten; fallback to original URL on failure
                    $token = $data['bitly_token'] ?? null;
                    if ($token) {
                        try {
                            return $this->shortenUrlBitly($data['url'], $token);
                        } catch (\Exception $e) {
                            // Log and gracefully degrade to original URL
                            Log::warning('Bitly shorten failed: ' . $e->getMessage());
                        }
                    }
                    // Fallback (static)
                    return $data['url'];
                }

                // 3. Static (direct link)
                return $data['url'];

            case 'text':
                return $data['text'];

            case 'email':
                $subject = $data['subject'] ?? '';
                $body = $data['body'] ?? '';
                return "mailto:{$data['email']}?subject=" . urlencode($subject) . "&body=" . urlencode($body);

            case 'phone':
                return "tel:{$data['phone']}";

            case 'sms':
                $message = $data['message'] ?? '';
                return "sms:{$data['phone']}" . ($message ? "?body=" . urlencode($message) : '');

            case 'wifi':
                $security = $data['security'] ?? 'WPA';
                $hidden = $data['hidden'] ?? false;
                return "WIFI:T:{$security};S:{$data['ssid']};P:{$data['password']};H:" . ($hidden ? 'true' : 'false') . ";;";

            case 'vcard':
                return $this->generateVCard($data);

            default:
                return $data['content'] ?? '';
        }
    }

    /**
     * Shorten a URL using Bitly API.
     */
    private function shortenUrlBitly(string $url, string $token): string
    {
        // Using Laravel HTTP client
        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->post('https://api-ssl.bitly.com/v4/shorten', [
                'long_url' => $url
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['link'] ?? $url;
        }

        throw new \Exception('Bitly API error: ' . $response->body());
    }

    /**
     * Update an existing Bitly short link with a new long URL.
     * Keeps the same bitlink so analytics/history are preserved.
     */
    private function updateBitlyLink(string $shortLink, string $newLongUrl, string $token): void
    {
        if (empty($shortLink)) {
            return;
        }

        // Extract the bitlink id (domain/hash)
        $bitlinkId = str_replace(['https://', 'http://'], '', $shortLink); // e.g., bit.ly/abc123

        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->patch("https://api-ssl.bitly.com/v4/bitlinks/{$bitlinkId}", [
                'long_url' => $newLongUrl
            ]);

        if (!$response->successful()) {
            $body = $response->json();
            $msg  = $body['message'] ?? 'Bitly API error';
            if ($response->status() === 403 || str_contains(strtolower($msg), 'plan')) {
                throw new \Exception('Bitly subscription required to update existing short links.');
            }
            throw new \Exception('Failed to update Bitly link: ' . $msg);
        }

        // Validate that Bitly actually changed the long_url
        $body = $response->json();
        $updatedLong = $body['long_url'] ?? null;
        if ($updatedLong !== $newLongUrl) {
            throw new \Exception('Bitly did not update the long URL. Your current Bitly plan may not support editing existing links.');
        }
    }

    /**
     * Generate vCard format
     */
    private function generateVCard(array $data): string
    {
        $vcard = "BEGIN:VCARD\n";
        $vcard .= "VERSION:3.0\n";
        $vcard .= "FN:{$data['name']}\n";

        if (!empty($data['phone'])) {
            $vcard .= "TEL:{$data['phone']}\n";
        }

        if (!empty($data['email'])) {
            $vcard .= "EMAIL:{$data['email']}\n";
        }

        if (!empty($data['organization'])) {
            $vcard .= "ORG:{$data['organization']}\n";
        }

        if (!empty($data['website'])) {
            $vcard .= "URL:{$data['website']}\n";
        }

        $vcard .= "END:VCARD";

        return $vcard;
    }

    /**
     * Create QR code for user (without saving image to storage)
     */
    public function createQrCode(int $userId, array $data): QrCode
    {
        $qrData = $this->generateQrData($data['type'], $data);

        // Create QR code record without filename since we're not saving images
        $qrCode = QrCode::create([
            'user_id' => $userId,
            'title' => $data['title'],
            'type' => $data['type'],
            'content' => json_encode($data),
            'qr_data' => $qrData,
            'filename' => null, // No file saved
            'settings' => $data['settings'] ?? [],
        ]);

        // For URL type QR codes, regenerate QR data with redirect URL now that we have the ID
        if ($data['type'] === 'url' && (($data['url_type'] ?? 'static') === 'hosted')) {
            $redirectQrData = $this->generateQrData($data['type'], $data, $qrCode);
            $qrCode->update(['qr_data' => $redirectQrData]);
        }

        return $qrCode;
    }

    /**
     * Update QR code content (without regenerating image file)
     */
    public function updateQrCode(QrCode $qrCode, array $data): QrCode
    {
        if ($data['type'] === 'url') {
            $urlType = $data['url_type'] ?? 'static';

            if ($urlType === 'dynamic') {
                // Re-use existing Bitly link if present, otherwise create a new one.
                $bitlyToken = $data['bitly_token'] ?? null;
                $existingShort = $qrCode->qr_data;

                if ($bitlyToken) {
                    if ($existingShort && \Illuminate\Support\Str::contains($existingShort, 'bit.ly/')) {
                        // Update long URL behind existing bitlink; may throw if not permitted
                        $this->updateBitlyLink($existingShort, $data['url'], $bitlyToken);
                        $qrData = $existingShort; // keep same short link
                    } else {
                        // No prior bitlink; create one
                        $qrData = $this->shortenUrlBitly($data['url'], $bitlyToken);
                    }
                } else {
                    $qrData = $data['url'];
                }
            } elseif ($urlType === 'hosted') {
                $qrData = route('qr.scan', $qrCode->id);
            } else { // static
                $qrData = $data['url'];
            }

            $qrCode->update([
                'title' => $data['title'],
                'type' => $data['type'],
                'content' => json_encode($data),
                'qr_data' => $qrData,
                'settings' => $data['settings'] ?? $qrCode->settings,
            ]);
        } else {
            // Non-URL QR codes: regenerate QR data as before
            $qrData = $this->generateQrData($data['type'], $data, $qrCode);
            $qrCode->update([
                'title' => $data['title'],
                'type' => $data['type'],
                'content' => json_encode($data),
                'qr_data' => $qrData,
                'settings' => $data['settings'] ?? $qrCode->settings,
            ]);
        }

        return $qrCode;
    }

    /**
     * Generate QR code using JavaScript (client-side) - kept for compatibility
     */
    public function getClientSideQrCode(string $data, int $size = 300): array
    {
        return [
            'data' => $data,
            'size' => $size,
            'client_side' => true
        ];
    }

    /**
     * Generate QR code directly as data URI (on-demand generation)
     */
    public function generateQrCodeDirect(string $data, array $settings = []): string
    {
        $size = $settings['size'] ?? 300;
        $errorCorrectionLevel = $settings['error_correction'] ?? 'M';
        $margin = $settings['margin'] ?? 10;

        // Map error correction levels for v6.0
        $errorLevelMap = [
            'L' => ErrorCorrectionLevel::Low,
            'M' => ErrorCorrectionLevel::Medium,
            'Q' => ErrorCorrectionLevel::Quartile,
            'H' => ErrorCorrectionLevel::High,
        ];

        try {
            $result = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: $errorLevelMap[$errorCorrectionLevel] ?? ErrorCorrectionLevel::Medium,
                size: $size,
                margin: $margin,
                roundBlockSizeMode: RoundBlockSizeMode::Margin
            );

            return $result->build()->getDataUri();

        } catch (\Exception $e) {
            Log::error('Direct QR Code generation failed: ' . $e->getMessage());
            throw new \Exception('Failed to generate QR code: ' . $e->getMessage());
        }
    }

    /**
     * Generate QR code image as PNG string (for direct output or API responses)
     */
    public function generateQrCodePng(string $data, array $settings = []): string
    {
        $size = $settings['size'] ?? 300;
        $errorCorrectionLevel = $settings['error_correction'] ?? 'M';
        $margin = $settings['margin'] ?? 10;

        // Map error correction levels
        $errorLevelMap = [
            'L' => ErrorCorrectionLevel::Low,
            'M' => ErrorCorrectionLevel::Medium,
            'Q' => ErrorCorrectionLevel::Quartile,
            'H' => ErrorCorrectionLevel::High,
        ];

        try {
            $result = new Builder(
                writer: new PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: $errorLevelMap[$errorCorrectionLevel] ?? ErrorCorrectionLevel::Medium,
                size: $size,
                margin: $margin,
                roundBlockSizeMode: RoundBlockSizeMode::Margin
            );

            return $result->build()->getString();

        } catch (\Exception $e) {
            Log::error('QR Code PNG generation failed: ' . $e->getMessage());
            throw new \Exception('Failed to generate QR code PNG: ' . $e->getMessage());
        }
    }

    /**
     * Get QR code types with their configurations
     */
    public function getQrCodeTypes(): array
    {
        return [
            'url' => [
                'name' => 'Website URL',
                'icon' => 'fas fa-link',
                'fields' => ['url_type', 'url', 'bitly_token', 'route_name', 'route_params']
            ],
            'text' => [
                'name' => 'Plain Text',
                'icon' => 'fas fa-font',
                'fields' => ['text']
            ],
            'email' => [
                'name' => 'Email',
                'icon' => 'fas fa-envelope',
                'fields' => ['email', 'subject', 'body']
            ],
            'phone' => [
                'name' => 'Phone Number',
                'icon' => 'fas fa-phone',
                'fields' => ['phone']
            ],
            'sms' => [
                'name' => 'SMS Message',
                'icon' => 'fas fa-sms',
                'fields' => ['phone', 'message']
            ],
            'wifi' => [
                'name' => 'WiFi Network',
                'icon' => 'fas fa-wifi',
                'fields' => ['ssid', 'password', 'security', 'hidden']
            ],
            'vcard' => [
                'name' => 'Contact Card',
                'icon' => 'fas fa-address-card',
                'fields' => ['name', 'phone', 'email', 'organization', 'website']
            ]
        ];
    }
}
