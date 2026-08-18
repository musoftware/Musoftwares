<?php

namespace Modules\DigitalProducts\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\DigitalProducts\Models\DigitalProduct;
use Modules\DigitalProducts\Models\DigitalProductDownload;

class FreeBookDownloadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DigitalProduct $product,
        public DigitalProductDownload $downloadRecord
    ) {}

    public function build(): self
    {
        $downloadUrl = route('library.download.token', ['token' => $this->downloadRecord->download_token]);

        return $this->subject('رابط تحميل كتابك: ' . $this->product->title)
            ->view('digitalproducts::emails.free-download-link', [
                'product' => $this->product,
                'downloadUrl' => $downloadUrl,
                'expiresAt' => $this->downloadRecord->token_expires_at,
            ]);
    }
}
