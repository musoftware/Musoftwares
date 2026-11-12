<?php

namespace Modules\AppBuilder\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Notifications\BuilderOwnerAlert;
use Throwable;

/**
 * The owner's alerts about the AI builder: a Telegram message with a button that opens the page to act on (the
 * channel that reliably reaches the owner's phone), and a push to every admin's browsers and apps. A failed alert
 * is logged and never stops the work that caused it.
 */
class OwnerAlerts
{
    private const TELEGRAM_SECONDS = 10;

    /** @return int how many channels took the alert (Telegram counts as one, plus one per admin pushed to) */
    public function send(BuilderProject $project, string $line, ?string $url = null): int
    {
        $title = mb_substr((string) ($project->title ?: 'AI builder'), 0, 120);
        $url ??= route('admin.builder.show', $project);

        return (int) $this->telegram($title, mb_substr($line, 0, 900), $url) + $this->push($project, $title, mb_substr($line, 0, 400), $url);
    }

    private function telegram(string $title, string $line, string $url): bool
    {
        $settings = (array) config('builder.owner_telegram');
        if (empty($settings['bot_token']) || empty($settings['chat_id'])) {
            return false;
        }

        try {
            $response = Http::timeout(self::TELEGRAM_SECONDS)->post('https://api.telegram.org/bot'.$settings['bot_token'].'/sendMessage', [
                'chat_id' => $settings['chat_id'],
                'text' => $title."\n\n".$line,
                'reply_markup' => ['inline_keyboard' => [[['text' => 'افتح', 'url' => $url]]]],
            ]);
        } catch (Throwable $error) {
            Log::warning('App builder: owner Telegram failed', ['error' => $error->getMessage()]);

            return false;
        }
        if (! $response->successful()) {
            // the reply never contains the token; the URL that does is not logged
            Log::warning('App builder: Telegram refused the owner alert', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
        }

        return $response->successful();
    }

    private function push(BuilderProject $project, string $title, string $line, string $url): int
    {
        // whereHas, not User::role(), because role() throws when the "admin" role does not exist yet
        $admins = User::whereHas('roles', fn ($roles) => $roles->where('name', 'admin'))->get();
        if ($admins->isEmpty()) {
            return 0;
        }

        try {
            Notification::send($admins, new BuilderOwnerAlert($title, $line, $url));
        } catch (Throwable $error) {
            Log::warning('App builder: owner push failed', ['project_id' => $project->id, 'error' => $error->getMessage()]);

            return 0;
        }

        return $admins->count();
    }
}
