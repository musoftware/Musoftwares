<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\ServiceLeadDownload;
use Modules\Marketplace\Models\Service;
use Illuminate\Support\Str;
use Exception;

class FreeDownloadService
{
    /**
     * Process gated free download submission with email lead capture.
     */
    public function processLeadDownload(Service $service, string $email, ?string $name = null, ?string $ip = null): ServiceLeadDownload
    {
        if (!$service->is_free) {
            throw new Exception("This service is not available for free download.");
        }

        $token = Str::random(40);

        $download = ServiceLeadDownload::create([
            'service_id' => $service->id,
            'email' => $email,
            'name' => $name ?? 'Guest',
            'ip_address' => $ip ?? request()->ip(),
            'download_token' => $token,
            'expires_at' => now('Africa/Cairo')->addHours(24),
            'downloaded_at' => null,
        ]);


        return $download;
    }

    /**
     * Verify download token and mark as downloaded.
     */
    public function verifyAndClaimDownload(string $token): ServiceLeadDownload
    {
        $download = ServiceLeadDownload::where('download_token', $token)->first();


        if (!$download) {
            throw new Exception("Invalid or expired download link.");
        }

        if ($download->expires_at && now('Africa/Cairo')->isAfter($download->expires_at)) {
            throw new Exception("Download token has expired.");
        }

        $download->update([
            'downloaded_at' => now('Africa/Cairo'),
        ]);

        return $download;
    }
}
