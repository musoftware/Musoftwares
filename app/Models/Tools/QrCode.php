<?php

namespace App\Models\Tools;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\QrCodeService;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'content',
        'qr_data',
        'filename',
        'status',
        'scans',
        'last_scanned_at',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_scanned_at' => 'datetime',
        'scans' => 'integer',
    ];

    /**
     * Get the user that owns the QR code
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increment scan count
     */
    public function incrementScans(): void
    {
        $this->increment('scans');
        $this->update(['last_scanned_at' => now()]);
    }

    /**
     * Get QR code image URL (served on-demand)
     */
    public function getImageUrlAttribute(): string
    {
        return route('qr-codes.image', $this);
    }

    /**
     * Get QR code as PNG binary data
     */
    public function getQrCodePng(): string
    {
        try {
            $qrCodeService = app(QrCodeService::class);
            return $qrCodeService->generateQrCodePng($this->qr_data, $this->settings ?? []);
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate QR code image: ' . $e->getMessage());
        }
    }

    /**
     * Get QR code as data URI for immediate use
     */
    public function getDataUri(): string
    {
        return $this->getImageUrlAttribute();
    }

    /**
     * Get formatted QR data based on type
     */
    public function getFormattedQrDataAttribute(): string
    {
        switch ($this->type) {
            case 'url':
                return $this->content;
            case 'text':
                return $this->content;
            case 'email':
                return "mailto:{$this->content}";
            case 'phone':
                return "tel:{$this->content}";
            case 'sms':
                $parts = explode('|', $this->content);
                return "sms:{$parts[0]}" . (isset($parts[1]) ? "?body={$parts[1]}" : '');
            case 'wifi':
                $settings = json_decode($this->content, true);
                return "WIFI:T:{$settings['security']};S:{$settings['ssid']};P:{$settings['password']};H:{$settings['hidden']};;";
            case 'vcard':
                return $this->content;
            default:
                return $this->content;
        }
    }
}
