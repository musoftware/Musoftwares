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

    /** The chat takes files up to 10 MB, like the page. */
    private const MAX_FILE_BYTES = 10 * 1024 * 1024;

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
     * @param  list<array{0: string, 1: string}>  $actions  buttons the bot answers itself: [label, callback data], two a row
     */
    public function send(string $chatId, string $text, array $links = [], array $menu = [], array $actions = []): bool
    {
        $payload = ['chat_id' => $chatId, 'text' => mb_substr($text, 0, self::MAX_TEXT), 'disable_web_page_preview' => true];
        if ($links !== [] || $actions !== []) {
            $payload['reply_markup'] = ['inline_keyboard' => [
                ...array_map(fn (array $row) => array_map(fn (array $action) => ['text' => $action[0], 'callback_data' => $action[1]], $row), array_chunk($actions, 2)),
                ...array_map(fn (array $link) => [['text' => $link[0], 'url' => $link[1]]], $links),
            ]];
        } elseif ($menu !== []) {
            $payload['reply_markup'] = ['keyboard' => array_map(fn (array $row) => array_map(fn (string $label) => ['text' => $label], $row), array_chunk($menu, 2)), 'resize_keyboard' => true];
        }

        return $this->call('sendMessage', $payload) !== null;
    }

    /** A picture or file someone sent the bot, or null when it is too big or cannot be read. */
    public function file(string $fileId): ?string
    {
        $file = $this->call('getFile', ['file_id' => $fileId]);
        if ($file === null || empty($file['file_path']) || (int) ($file['file_size'] ?? 0) > self::MAX_FILE_BYTES) {
            return null;
        }

        try {
            $response = Http::timeout(self::SECONDS * 3)->get('https://api.telegram.org/file/bot'.$this->token().'/'.$file['file_path']);
        } catch (Throwable $error) {
            Log::warning('Telegram file download failed', ['error' => Str::of($error->getMessage())->replace($this->token(), '***')->value()]);

            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    /** Closes the spinner on a pressed button, with a short line on top of the chat. */
    public function answerButton(string $callbackId, string $text): void
    {
        $this->call('answerCallbackQuery', ['callback_query_id' => $callbackId, 'text' => mb_substr($text, 0, 190)]);
    }

    /** Telegram sends every message to the site at once, signed with this secret. */
    public function setWebhook(string $url): bool
    {
        return $this->call('setWebhook', ['url' => $url, 'secret_token' => $this->webhookSecret(), 'allowed_updates' => ['message', 'callback_query'], 'drop_pending_updates' => true]) !== null;
    }

    /**
     * The list Telegram shows when someone types "/": one for Arabic phones (the default) and one for English ones.
     *
     * @param  array<string, array<string, string>>  $byLanguage  language => [command => description]
     */
    public function setCommands(array $byLanguage): bool
    {
        $ok = true;
        foreach ($byLanguage as $language => $commands) {
            $list = array_map(fn (string $command, string $description) => ['command' => $command, 'description' => $description], array_keys($commands), $commands);
            $payload = ['commands' => $list] + ($language === 'ar' ? [] : ['language_code' => $language]);
            $ok = $this->call('setMyCommands', $payload) !== null && $ok;
        }

        return $ok;
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
