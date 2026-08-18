<?php

namespace Modules\DigitalProducts\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Models\DigitalProductDownload;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Exception;

class DownloadTokenService
{
    /**
     * Create a download record with unique secure token for free or email-verified access.
     */
    public function generateTokenForFreeBook(DigitalProduct $product, string $email, ?User $user = null, string $editionType = 'full'): DigitalProductDownload
    {
        $hours = config('digitalproducts.download_token_lifetime_hours', 48);

        return DigitalProductDownload::create([
            'digital_product_id' => $product->id,
            'edition_type' => $editionType,
            'user_id' => $user?->id,
            'email' => strtolower(trim($email)),
            'download_token' => Str::random(64),
            'token_expires_at' => now()->addHours($hours),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Verify token and serve binary file download response.
     */
    public function serveDownloadByToken(string $token): BinaryFileResponse
    {
        $download = DigitalProductDownload::with('product')
            ->where('download_token', $token)
            ->firstOrFail();

        if (!$download->isValid()) {
            throw new Exception(__('digitalproducts.download_link_expired'));
        }

        $product = $download->product;
        $isPlaybook = ($download->edition_type === 'playbook');
        $filePath = ($isPlaybook && $product->free_edition_file_path)
            ? $product->free_edition_file_path
            : $product->file_path;

        if (!$product || empty($filePath) || !Storage::disk('local')->exists($filePath)) {
            throw new Exception(__('digitalproducts.file_not_found'));
        }

        // Record download stats
        $download->increment('download_count');
        $download->update([
            'last_downloaded_at' => now(),
            'ip_address' => request()->ip(),
        ]);

        if ($isPlaybook) {
            $product->increment('free_edition_download_count');
        } else {
            $product->increment('download_count');
        }

        $fullPath = Storage::disk('local')->path($filePath);
        $prefix = $isPlaybook ? 'playbook-' : '';
        $cleanFileName = $prefix . (Str::slug($product->title) ?: 'book') . '.pdf';

        return response()->download($fullPath, $cleanFileName, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Serve direct permanent download for authenticated user who purchased the product.
     */
    public function serveDownloadForPurchaser(User $user, DigitalProduct $product): BinaryFileResponse
    {
        if (!$product->is_free && !$product->isPurchasedBy($user) && !$user->hasRole('admin')) {
            throw new Exception(__('digitalproducts.access_denied_purchase_required'));
        }

        if (!Storage::disk('local')->exists($product->file_path)) {
            throw new Exception(__('digitalproducts.file_not_found'));
        }

        $product->increment('download_count');

        $fullPath = Storage::disk('local')->path($product->file_path);
        $cleanFileName = Str::slug($product->title) . '.pdf';

        return response()->download($fullPath, $cleanFileName, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
