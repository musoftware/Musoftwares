<?php

namespace Modules\AppBuilder\Services;

use App\Models\User;
use App\Services\Telegram\TelegramApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatLimitReached;
use Modules\AppBuilder\Services\Chat\ChatUnavailable;
use Modules\AppBuilder\Services\Chat\ProjectChat;
use Throwable;

/**
 * An AI builder chat in Telegram: the customer picks one of their projects (or starts a new one) and writes to it
 * from the bot, pictures and files included. It is the same chat as on the site, with the same rules. Every answer,
 * team line and delivery of the chosen project comes back to Telegram; payment and downloads stay links to the page.
 */
class TelegramChat
{
    private const KEY = 'telegram_builder_chat_';

    private const DAYS = 30;

    private const NEW_CHATS_PER_DAY = 5;

    public function __construct(private readonly TelegramApi $api, private readonly ProjectChat $chat) {}

    /** The project this Telegram chat is talking to, if any (only one of this account's). */
    public function active(User $user, string $chatId): ?BuilderProject
    {
        $id = Cache::store('file')->get(self::KEY.$chatId);

        return $id ? BuilderProject::whereKey($id)->where('user_id', $user->id)->first() : null;
    }

    public function enter(User $user, string $chatId, int $projectId): string
    {
        $project = BuilderProject::whereKey($projectId)->where('user_id', $user->id)->first();
        if ($project === null) {
            return __('telegram.chat_not_yours');
        }
        Cache::store('file')->put(self::KEY.$chatId, $project->id, now()->addDays(self::DAYS));
        $last = $project->messages()->whereIn('role', [BuilderMessage::ASSISTANT, BuilderMessage::TEAM])->reorder('id', 'desc')->value('content');

        return __('telegram.chat_entered', ['title' => $project->title ?: __('telegram.untitled')])
            .($last ? "\n\n".__('telegram.chat_last').":\n".mb_substr((string) $last, 0, 1500) : '');
    }

    /** A new chat for this account, the same as "start a new chat" on the site. */
    public function startNew(User $user, string $chatId): string
    {
        try {
            $project = $this->chat->start('telegram:'.$user->id, app()->getLocale(), null, $user->id, $user->isAdmin());
        } catch (ChatLimitReached) {
            return __('builder.errors.limit');
        }
        Cache::store('file')->put(self::KEY.$chatId, $project->id, now()->addDays(self::DAYS));

        return __('telegram.chat_new');
    }

    public function leave(string $chatId): void
    {
        Cache::store('file')->forget(self::KEY.$chatId);
    }

    /**
     * The customer's message goes to the chosen project, like a message on the page. The answer reaches Telegram
     * through forward(), as every answer of this project does.
     *
     * @param  list<UploadedFile>  $files
     */
    public function say(BuilderProject $project, string $chatId, string $text, array $files = []): void
    {
        $refusal = $this->refusal($project);
        if ($refusal !== null) {
            $this->api->send($chatId, $refusal);

            return;
        }
        if ($project->isWaitingForAnswer()) {
            $answer = app(BuildQuestions::class)->answer($project, $text, BuilderMessage::USER);
            app(AttachmentStore::class)->attach($answer, $files);
            $this->api->send($chatId, __('telegram.chat_answer_sent'));

            return;
        }

        $before = $project->requests()->pluck('id')->all();
        try {
            $this->chat->send($project, $text !== '' ? $text : '...', $files);
        } catch (\Modules\AppBuilder\Services\Chat\SignInNeeded|ChatLimitReached) {
            $this->api->send($chatId, __('builder.errors.limit'));

            return;
        } catch (ChatUnavailable) {
            $this->api->send($chatId, __('builder.errors.unavailable'));

            return;
        }
        $this->tellNewRequests($project->refresh(), $chatId, $before);
    }

    /** A line of the active project reached the customer's Telegram (the chat's answer, the team, a delivery). */
    public function forward(BuilderMessage $message): void
    {
        $project = $message->project;
        $chatId = (string) $project?->user?->telegram_chat_id;
        if ($chatId === '' || (int) Cache::store('file')->get(self::KEY.$chatId) !== $project->id || trim((string) $message->content) === '') {
            return;
        }

        try {
            app()->setLocale($project->customerLanguage());
            $this->api->send($chatId, $this->plain((string) $message->content), [[__('telegram.chat_open_page'), route('builder.show', $project)]]);
        } catch (Throwable $error) {
            Log::warning('Telegram: a chat line was not forwarded', ['message_id' => $message->id, 'error' => $error->getMessage()]);
        }
    }

    /** What the page would refuse, said in Telegram. */
    private function refusal(BuilderProject $project): ?string
    {
        return match (true) {
            ! $project->isOpenForChat() => __('builder.errors.busy'),
            (bool) $project->awaiting_look => __('builder.errors.looking'),
            $project->hasBalanceDue() && $project->hasDelivery() => __('builder.errors.balance_due'),
            default => null,
        };
    }

    /** A request this message added: its price and where to pay, since Telegram has no list beside the chat. */
    private function tellNewRequests(BuilderProject $project, string $chatId, array $before): void
    {
        $new = $project->requests()->whereNotIn('id', $before)->get();
        foreach ($new as $request) {
            $price = $request->free ? __('builder.requests.free') : number_format((float) $request->amount, 2).' '.$project->currency;
            $this->api->send($chatId, __('telegram.chat_request_added', ['title' => $request->title, 'price' => $price]), [[__('telegram.chat_open_page'), route('builder.show', $project)]]);
        }
    }

    /** Telegram shows text as written: the Markdown marks the page draws are taken out. */
    private function plain(string $text): string
    {
        return trim((string) preg_replace(['/\*\*(.+?)\*\*/u', '/^#{1,6}\s*/mu', '/`([^`]+)`/u'], ['$1', '', '$1'], $text));
    }
}
