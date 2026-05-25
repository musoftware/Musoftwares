<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;

class GoldProviderDisconnectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly GoldMarketSource $source,
        public readonly string           $reason,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Gold Price Provider Disconnected')
            ->greeting("Hello {$notifiable->name},")
            ->line("The gold price provider **{$this->source->name}** for market **{$this->source->market_key}** has been disconnected.")
            ->line("Reason: {$this->reason}")
            ->line('The system has automatically switched to the next available provider.')
            ->action('View Provider Status', url('/isaas/gold-savers/live-prices/sources'))
            ->line('Please investigate and restore the primary provider.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'        => 'gold_provider_disconnected',
            'source_id'   => $this->source->id,
            'source_name' => $this->source->name,
            'market_key'  => $this->source->market_key,
            'reason'      => $this->reason,
        ];
    }
}
