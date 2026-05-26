<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;

class GoldStalePriceDetectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string        $marketKey,
        public readonly ?GoldLivePrice $livePrice,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $lastFetch = $this->livePrice?->fetched_at?->diffForHumans() ?? 'unknown';

        return (new MailMessage)
            ->subject('🕐 Gold Market Data is Stale')
            ->line("The gold price for market **{$this->marketKey}** has not been updated recently.")
            ->line("Last successful fetch: {$lastFetch}")
            ->line('Live portfolio valuations may be inaccurate until prices are refreshed.')
            ->action('View Live Prices', url('/isaas/gold-savers/live-prices'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type'       => 'gold_stale_price',
            'market_key' => $this->marketKey,
            'last_fetch' => $this->livePrice?->fetched_at?->toISOString(),
        ];
    }
}
