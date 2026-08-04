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
            throw new Exception("هذه الخدمة غير متاحة للتحميل المجاني.");
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
            throw new Exception("رابط التحميل غير صالح أو غير موجود.");
        }

        if ($download->expires_at && now('Africa/Cairo')->isAfter($download->expires_at)) {
            throw new Exception("انتهت صلاحية رابط التحميل المجاني.");
        }

        $download->update([
            'downloaded_at' => now('Africa/Cairo'),
        ]);

        return $download;
    }
}
