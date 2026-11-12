<?php

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Ties a site account to a Telegram chat: the settings page opens the bot with a one-time code, the user presses
 * Start, and the bot (through the webhook) finds the account the code was made for. Codes live in the file cache:
 * production's default cache forgets everything after each request.
 */
class TelegramLinks
{
    private const MINUTES = 15;

    private const PREFIX = 'telegram_link_';

    public function __construct(private readonly TelegramApi $api) {}

    public function linkUrl(User $user): ?string
    {
        $bot = $this->api->botUsername();
        if ($bot === null) {
            return null;
        }
        $code = Str::random(24);
        Cache::store('file')->put(self::PREFIX.$code, $user->id, now()->addMinutes(self::MINUTES));

        return 'https://t.me/'.$bot.'?start='.$code;
    }

    /** The account the code was made for, now tied to this chat; null for an unknown or used code. */
    public function link(string $code, string $chatId): ?User
    {
        if (! preg_match('/^[A-Za-z0-9]{24}$/', $code)) {
            return null;
        }
        $user = User::find(Cache::store('file')->pull(self::PREFIX.$code));
        if ($user === null) {
            return null;
        }
        // one chat, one account: a chat linked before is moved to the new account
        User::where('telegram_chat_id', $chatId)->whereKeyNot($user->id)->update(['telegram_chat_id' => null]);
        $user->forceFill(['telegram_chat_id' => $chatId])->save();

        return $user;
    }

    public function userFor(string $chatId): ?User
    {
        return User::where('telegram_chat_id', $chatId)->first();
    }

    public function unlink(User $user): void
    {
        $user->forceFill(['telegram_chat_id' => null])->save();
    }
}
