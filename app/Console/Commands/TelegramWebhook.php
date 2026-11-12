<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramApi;
use Illuminate\Console\Command;

/** Tells Telegram to deliver the bot's messages to the site. Run once after setting the bot token, or after a domain change. */
class TelegramWebhook extends Command
{
    protected $signature = 'telegram:webhook';

    protected $description = "Point the site's Telegram bot at the site's webhook";

    public function handle(TelegramApi $api): int
    {
        if (! $api->configured()) {
            $this->error('No bot token (SITE_TELEGRAM_BOT_TOKEN).');

            return self::FAILURE;
        }
        $url = route('telegram.webhook');
        if (! $api->setWebhook($url)) {
            $this->error('Telegram refused the webhook; see the log.');

            return self::FAILURE;
        }

        $this->info('Webhook set for @'.$api->botUsername().': '.$url);

        return self::SUCCESS;
    }
}
