<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/** The site's Telegram bot: messages with buttons, the bot's name, and its webhook. The token stays on the server. */
class TelegramApi
{
    private const SECONDS = 10;

    /** Telegram's own limit for one message. */
    private const MAX_TEXT = 4000;

    public function configured(): bool
    {
        return $this->token() !== '';
    }

    /** The bot's @name, asked from Telegram once a day. */
    public function botUsername(): ?string
    {
        if (! $this->configured()) {
            return null;
        }

        return Cache::store('file')->remember('telegram_bot_username', now()->addDay(), fn () => $this->call('getMe')['username'] ?? null);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $links  buttons that open a page: [label, url]
     * @param  list<string>  $menu  the buttons kept under the typing box
     */
    public function send(string $chatId, string $text, array $links = [], array $menu = []): bool
    {
        $payload = ['chat_id' => $chatId, 'text' => mb_substr($text, 0, self::MAX_TEXT), 'disable_web_page_preview' => true];
        if ($links !== []) {
            $payload['reply_markup'] = ['inline_keyboard' => array_map(fn (array $link) => [['text' => $link[0], 'url' => $link[1]]], $links)];
        } elseif ($menu !== []) {
            $payload['reply_markup'] = ['keyboard' => array_map(fn (array $row) => array_map(fn (string $label) => ['text' => $label], $row), array_chunk($menu, 2)), 'resize_keyboard' => true];
        }

        return $this->call('sendMessage', $payload) !== null;
    }

    /** Telegram sends every message to the site at once, signed with this secret. */
    public function setWebhook(string $url): bool
    {
        return $this->call('setWebhook', ['url' => $url, 'secret_token' => $this->webhookSecret(), 'allowed_updates' => ['message'], 'drop_pending_updates' => true]) !== null;
    }

    /** Derived from the app key, so there is no second secret to keep. */
    public function webhookSecret(): string
    {
        return substr(hash_hmac('sha256', 'telegram-webhook', (string) config('app.key')), 0, 48);
    }

    /** @return array<mixed>|null Telegram's result, or null when it failed (logged without the token) */
    private function call(string $method, array $payload = []): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $response = Http::timeout(self::SECONDS)->post('https://api.telegram.org/bot'.$this->token().'/'.$method, $payload);
        } catch (Throwable $error) {
            Log::warning('Telegram '.$method.' failed', ['error' => Str::of($error->getMessage())->replace($this->token(), '***')->value()]);

            return null;
        }
        if (! $response->successful() || ! $response->json('ok')) {
            Log::warning('Telegram refused '.$method, ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);

            return null;
        }

        return (array) $response->json('result');
    }

    private function token(): string
    {
        return (string) config('services.site_telegram.bot_token');
    }
}
