<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class WhatsAppMediaService
{
    protected string $disk;
    protected int $maxImageWidth = 2000;
    protected int $imageQuality = 80;

    public function __construct()
    {
        $this->disk = config('filesystems.whatsapp_media_disk', 'local');
    }

    /**
     * Upload a media file from an agent.
     *
     * @return array{url: string, mime_type: string, size: int, filename: string, thumbnail_url: ?string}
     */
    public function upload(UploadedFile $file, int $workspaceId): array
    {
        $this->validateFile($file);

        $filename = $this->generateFilename($file);
        $path = "whatsapp/{$workspaceId}/" . now()->format('Y/m');

        // Store the file
        $storedPath = $file->storeAs($path, $filename, $this->disk);

        $result = [
            'url'       => Storage::disk($this->disk)->url($storedPath),
            'path'      => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize(),
            'filename'  => $file->getClientOriginalName(),
            'thumbnail_url' => null,
        ];

        // Generate thumbnail for images
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $result['thumbnail_url'] = $this->generateThumbnail($file, $path, $workspaceId);
        }

        return $result;
    }

    /**
     * Process incoming media from WhatsApp provider (download and store).
     *
     * @return array{url: string, mime_type: string, size: int, thumbnail_url: ?string}
     */
    public function processIncomingMedia(string $providerUrl, string $mimeType, int $workspaceId): array
    {
        $path = "whatsapp/{$workspaceId}/" . now()->format('Y/m');
        $extension = $this->getExtensionFromMime($mimeType);
        $filename = Str::uuid() . '.' . $extension;

        // Download from provider
        $contents = file_get_contents($providerUrl);
        $storedPath = "{$path}/{$filename}";

        Storage::disk($this->disk)->put($storedPath, $contents);

        $result = [
            'url'           => Storage::disk($this->disk)->url($storedPath),
            'path'          => $storedPath,
            'mime_type'     => $mimeType,
            'size'          => strlen($contents),
            'thumbnail_url' => null,
        ];

        // Generate thumbnail for images
        if (str_starts_with($mimeType, 'image/')) {
            $thumbPath = "{$path}/thumbs/{$filename}";
            $this->createThumbnailFromContents($contents, $thumbPath);
            $result['thumbnail_url'] = Storage::disk($this->disk)->url($thumbPath);
        }

        return $result;
    }

    /**
     * Get a secure (signed) URL for a media file.
     */
    public function getSecureUrl(string $path, int $expiryMinutes = 60): string
    {
        if ($this->disk === 's3' || $this->disk === 'do') {
            return Storage::disk($this->disk)->temporaryUrl($path, now()->addMinutes($expiryMinutes));
        }

        // For local storage, return the regular URL
        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Clean up old media files based on retention policy.
     */
    public function cleanup(int $retentionDays = 90): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        $deleted = 0;

        // Find messages with media older than retention period
        $oldMessages = \Modules\CRM\Models\WhatsAppMessage::withoutGlobalScopes()
            ->whereNotNull('media_url')
            ->where('created_at', '<', $cutoffDate)
            ->cursor();

        foreach ($oldMessages as $message) {
            if ($message->metadata['media_path'] ?? null) {
                Storage::disk($this->disk)->delete($message->metadata['media_path']);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Validate uploaded file against allowed types and sizes.
     */
    protected function validateFile(UploadedFile $file): void
    {
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/quicktime', 'video/webm',
            'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv',
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException("File type {$file->getMimeType()} is not allowed.");
        }

        // 64MB max for videos, 16MB for everything else
        $maxSize = str_starts_with($file->getMimeType(), 'video/') ? 64 * 1024 * 1024 : 16 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException("File exceeds maximum size limit.");
        }
    }

    protected function generateFilename(UploadedFile $file): string
    {
        return Str::uuid() . '.' . $file->getClientOriginalExtension();
    }

    protected function generateThumbnail(UploadedFile $file, string $path, int $workspaceId): ?string
    {
        try {
            $thumbFilename = 'thumb_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $thumbPath = "{$path}/thumbs/{$thumbFilename}";

            // Use GD or Imagick if available
            $image = \Intervention\Image\ImageManager::gd()->read($file->getRealPath());
            $image->scaleDown(width: 300);
            $thumbContents = $image->toJpeg(60)->toString();

            Storage::disk($this->disk)->put($thumbPath, $thumbContents);

            return Storage::disk($this->disk)->url($thumbPath);
        } catch (\Exception $e) {
            // Thumbnail generation is optional
            return null;
        }
    }

    protected function createThumbnailFromContents(string $contents, string $thumbPath): void
    {
        try {
            $image = \Intervention\Image\ImageManager::gd()->read($contents);
            $image->scaleDown(width: 300);
            $thumbContents = $image->toJpeg(60)->toString();

            Storage::disk($this->disk)->put($thumbPath, $thumbContents);
        } catch (\Exception $e) {
            // Thumbnail generation is optional
        }
    }

    protected function getExtensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg'  => 'jpg',
            'image/png'   => 'png',
            'image/gif'   => 'gif',
            'image/webp'  => 'webp',
            'video/mp4'   => 'mp4',
            'audio/mpeg'  => 'mp3',
            'audio/ogg'   => 'ogg',
            'application/pdf' => 'pdf',
            default        => 'bin',
        };
    }
}
