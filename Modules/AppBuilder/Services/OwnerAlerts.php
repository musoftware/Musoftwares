<?php

namespace Modules\AppBuilder\Services;

use App\Models\User;
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
    /** @return int how many channels took the alert (Telegram counts as one, plus one per admin pushed to) */
    public function send(BuilderProject $project, string $line, ?string $url = null): int
    {
        $title = mb_substr((string) ($project->title ?: 'AI builder'), 0, 120);
        $url ??= route('admin.builder.show', $project);

        return (int) $this->telegram($title, mb_substr($line, 0, 900), $url) + $this->push($project, $title, mb_substr($line, 0, 400), $url);
    }

    /** Every admin who linked Telegram from their settings gets it there, with a button to the page. */
    private function telegram(string $title, string $line, string $url): bool
    {
        $telegram = app(\App\Services\Telegram\TelegramApi::class);
        if (! $telegram->configured()) {
            return false;
        }

        return $this->admins()->pluck('telegram_chat_id')->filter()->unique()
            ->map(fn (string $chatId) => $telegram->send($chatId, $title."\n\n".$line, [['افتح', $url]]))
            ->contains(true);
    }

    private function push(BuilderProject $project, string $title, string $line, string $url): int
    {
        $admins = $this->admins();
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

    private function admins()
    {
        // whereHas, not User::role(), because role() throws when the "admin" role does not exist yet
        return User::whereHas('roles', fn ($roles) => $roles->where('name', 'admin'))->get();
    }
}
