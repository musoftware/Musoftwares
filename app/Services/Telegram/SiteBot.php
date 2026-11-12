<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Services\AiAssistant\AssistantChat;
use App\Services\AiAssistant\AssistantLimiter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Services\OwnerApprovals;
use Modules\AppBuilder\Services\TelegramChat;
use Throwable;

/**
 * The site in Telegram, for a linked account: invoices, AI builder projects, balance, invite link, support tickets,
 * the bot's settings, and the site's AI assistant for any question. Admins also get the approvals here. An unlinked
 * chat is told to link from the settings page; nothing about an account is shown before that.
 */
class SiteBot
{
    private const MAX_QUESTION = 2000;

    /** The buttons under the typing box, in order; an admin also gets the approvals first. */
    private const BUTTONS = ['invoices', 'projects', 'balance', 'tickets', 'invite', 'settings', 'support'];

    /** The same screens as commands, listed when someone types "/" (/projects, /balance...). */
    public const COMMANDS = ['projects', 'invoices', 'balance', 'tickets', 'invite', 'settings', 'support'];

    public function __construct(
        private readonly TelegramApi $api,
        private readonly TelegramLinks $links,
        private readonly AssistantChat $assistant,
        private readonly AssistantLimiter $limiter,
        private readonly AccountScreens $screens,
    ) {}

    /** @param  array<string, mixed>  $update  one update from Telegram's webhook */
    public function handle(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->pressed($update['callback_query']);

            return;
        }
        $message = $update['message'] ?? [];
        $chatId = (string) ($message['chat']['id'] ?? '');
        // a picture or a file comes with its caption as the text
        $text = trim((string) ($message['text'] ?? $message['caption'] ?? ''));
        $hasFile = isset($message['photo']) || isset($message['document']);
        if ($chatId === '' || ($message['chat']['type'] ?? '') !== 'private' || ($text === '' && ! $hasFile)) {
            return;
        }
        $this->speak(null, (string) ($message['from']['language_code'] ?? 'ar'));

        if (str_starts_with($text, '/start')) {
            $this->start($chatId, trim(substr($text, strlen('/start'))));

            return;
        }
        $user = $this->links->userFor($chatId);
        if ($user === null) {
            $this->api->send($chatId, __('telegram.not_linked', ['url' => route('client.settings')]));

            return;
        }

        $this->speak($user, (string) ($message['from']['language_code'] ?? 'ar'));
        $this->answer($user, $chatId, $text, $message);
    }

    /** The language the customer picked in the bot, else their phone's: Arabic first. */
    private function speak(?User $user, string $phoneLanguage): void
    {
        $picked = $user?->telegram_locale;
        app()->setLocale(in_array($picked, ['ar', 'en'], true) ? $picked : (str_starts_with($phoneLanguage, 'en') ? 'en' : 'ar'));
    }

    /** A button: a project chat, a new chat, the bot's settings (any account), or an approval card (admins only). */
    private function pressed(array $button): void
    {
        $chatId = (string) ($button['message']['chat']['id'] ?? '');
        $user = $chatId !== '' ? $this->links->userFor($chatId) : null;
        $this->speak($user, (string) ($button['from']['language_code'] ?? 'ar'));
        $data = (string) ($button['data'] ?? '');
        $reply = match (true) {
            $user === null => __('telegram.not_linked', ['url' => route('client.settings')]),
            (bool) preg_match('/^ch:(\d{1,10})$/', $data, $match) => app(TelegramChat::class)->enter($user, $chatId, (int) $match[1]),
            $data === 'nw:0' => app(TelegramChat::class)->startNew($user, $chatId),
            $data === 'al:t' => $this->toggleAlerts($user),
            (bool) preg_match('/^lg:(ar|en)$/', $data, $match) => $this->switchLanguage($user, $chatId, $match[1]),
            $user->isAdmin() => app(OwnerApprovals::class)->act($chatId, $data),
            default => __('telegram.chat_not_yours'),
        };
        $this->api->answerButton((string) ($button['id'] ?? ''), mb_substr($reply, 0, 190));
        if ($reply !== '') {
            $this->api->send($chatId, $reply, menu: $user !== null ? $this->menu($user) : []);
        }
    }

    private function toggleAlerts(User $user): string
    {
        $user->forceFill(['telegram_alerts_off' => ! $user->telegram_alerts_off])->save();

        return $user->telegram_alerts_off ? __('telegram.alerts_now_off') : __('telegram.alerts_now_on');
    }

    /** Saved for the account; the menu comes back in the new language at once. */
    private function switchLanguage(User $user, string $chatId, string $locale): string
    {
        $user->forceFill(['telegram_locale' => $locale])->save();
        app()->setLocale($locale);
        $this->api->send($chatId, __('telegram.language_set'), menu: $this->menu($user));

        return '';
    }

    private function start(string $chatId, string $code): void
    {
        $user = $code !== '' ? $this->links->link($code, $chatId) : $this->links->userFor($chatId);
        if ($user === null) {
            $this->api->send($chatId, __('telegram.not_linked', ['url' => route('client.settings')]));

            return;
        }

        $this->api->send($chatId, __('telegram.welcome', ['name' => $user->name]), menu: $this->menu($user));
    }

    /** @param  array<string, mixed>  $message */
    private function answer(User $user, string $chatId, string $text, array $message): void
    {
        // a price the owner was asked for on an approval card
        if ($user->isAdmin() && app(OwnerApprovals::class)->priceTyped($chatId, $text)) {
            return;
        }
        match ($this->button($text)) {
            'approvals' => $user->isAdmin() ? app(OwnerApprovals::class)->sendWaiting($chatId) : $this->ask($user, $chatId, $text),
            'invoices' => $this->screens->invoices($user, $chatId, $this->menu($user)),
            'projects' => $this->screens->projects($user, $chatId),
            'balance' => $this->screens->balance($user, $chatId, $this->menu($user)),
            'tickets' => $this->screens->tickets($user, $chatId),
            'invite' => $this->screens->invite($user, $chatId),
            'settings' => $this->screens->settings($user, $chatId),
            'support' => $this->toAssistant($user, $chatId),
            default => $this->talk($user, $chatId, $text, $message),
        };
    }

    /** Which menu button the text is, in either language (the keyboard may still show the language before a switch). */
    private function button(string $text): ?string
    {
        // a command from the "/" list (/projects, or /projects@bot in a group-style tap)
        $command = preg_match('/^\/([a-z]+)(@\w+)?$/', $text, $match) ? $match[1] : null;
        if ($command !== null && in_array($command, [...self::COMMANDS, 'approvals'], true)) {
            return $command;
        }
        foreach (['approvals', ...self::BUTTONS] as $key) {
            if (in_array($text, [__('telegram.btn_'.$key, [], 'ar'), __('telegram.btn_'.$key, [], 'en')], true)) {
                return $key;
            }
        }

        return null;
    }

    /** Leaves any project chat: from here on, messages are questions for the site's assistant. */
    private function toAssistant(User $user, string $chatId): void
    {
        app(TelegramChat::class)->leave($chatId);
        $this->api->send($chatId, __('telegram.support_prompt'), menu: $this->menu($user));
    }

    /** In a project chat the message goes to that project's AI; otherwise to the site's assistant. */
    private function talk(User $user, string $chatId, string $text, array $message): void
    {
        $project = app(TelegramChat::class)->active($user, $chatId);
        if ($project === null) {
            $text !== '' ? $this->ask($user, $chatId, $text) : $this->api->send($chatId, __('telegram.chat_pick_first'), menu: $this->menu($user));

            return;
        }

        app(TelegramChat::class)->say($project, $chatId, $text, $this->files($message));
    }

    /**
     * The picture or file the customer sent, as an upload the chat takes like one from the page.
     *
     * @return list<UploadedFile>
     */
    private function files(array $message): array
    {
        $photo = $message['photo'] ?? null;
        $file = is_array($photo) && $photo !== [] ? ['id' => end($photo)['file_id'] ?? '', 'name' => 'photo.jpg', 'mime' => 'image/jpeg']
            : (isset($message['document']) ? ['id' => $message['document']['file_id'] ?? '', 'name' => (string) ($message['document']['file_name'] ?? 'file'), 'mime' => (string) ($message['document']['mime_type'] ?? '')] : null);
        $bytes = $file !== null && $file['id'] !== '' ? $this->api->file((string) $file['id']) : null;
        if ($bytes === null) {
            return [];
        }

        $path = tempnam(sys_get_temp_dir(), 'tg');
        file_put_contents($path, $bytes);

        return [new UploadedFile($path, basename($file['name']), $file['mime'] ?: null, null, true)];
    }

    /** Any other message is a question for the site's AI assistant, with the same limits as on the site. */
    private function ask(User $user, string $chatId, string $text): void
    {
        if (! $this->limiter->allow(['user:'.$user->id], 'telegram:'.$chatId)) {
            $this->api->send($chatId, AssistantChat::limitMessage(), menu: $this->menu($user));

            return;
        }

        try {
            $reply = $this->assistant->answer(mb_substr($text, 0, self::MAX_QUESTION), $user);
        } catch (Throwable $error) {
            Log::error('Telegram assistant reply failed', ['user_id' => $user->id, 'error' => $error->getMessage()]);
            $reply = AssistantChat::fallbackMessage();
        }
        $this->api->send($chatId, $reply, menu: $this->menu($user));
    }

    /** @return list<string> */
    private function menu(User $user): array
    {
        $keys = $user->isAdmin() ? ['approvals', ...self::BUTTONS] : self::BUTTONS;

        return array_map(fn (string $key) => __('telegram.btn_'.$key), $keys);
    }
}
