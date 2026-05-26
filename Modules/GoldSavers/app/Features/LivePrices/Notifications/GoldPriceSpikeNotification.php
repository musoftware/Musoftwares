<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GoldPriceSpikeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $marketKey,
        public readonly float  $detectedPrice,
        public readonly float  $changePct,
        public readonly string $anomalyType,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🚨 Abnormal Gold Price Spike Detected')
            ->line("An abnormal price was detected for market **{$this->marketKey}**.")
            ->line("Detected price: **{$this->detectedPrice} EGP/gram** (change: {$this->changePct}%)")
            ->line("Anomaly type: {$this->anomalyType}")
            ->line('This price has been **blocked** and the live price was NOT updated.')
            ->action('View Price Events', url('/isaas/gold-savers/live-prices/events'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type'           => 'gold_price_spike',
            'market_key'     => $this->marketKey,
            'detected_price' => $this->detectedPrice,
            'change_pct'     => $this->changePct,
            'anomaly_type'   => $this->anomalyType,
        ];
    }
}
