<?php

namespace App\Services\Telegram;

use App\Models\User;

/**
 * News for a customer in the site's Telegram bot: their build, an unpaid invoice, a support ticket. Only an account
 * that linked Telegram and kept alerts on gets them. An alert never breaks what sent it.
 */
class CustomerAlerts
{
    public function __construct(private readonly TelegramApi $api) {}

    /** @param  list<array{0: string, 1: string}>  $links  buttons that open a page: [label, url] */
    public function send(?User $user, string $text, array $links = []): bool
    {
        if (! $this->reaches($user) || ! $this->api->configured()) {
            return false;
        }

        return $this->api->send((string) $user->telegram_chat_id, $text, $links);
    }

    public function reaches(?User $user): bool
    {
        return $user !== null && (string) $user->telegram_chat_id !== '' && ! $user->telegram_alerts_off;
    }

    /** The bot speaks the language the customer picked in it, Arabic until they pick. */
    public static function localeOf(User $user): string
    {
        return in_array($user->telegram_locale, ['ar', 'en'], true) ? $user->telegram_locale : 'ar';
    }
}
