<?php

namespace App\Services\Telegram;

use App\Models\Invoice;
use App\Models\User;
use App\Services\AiAssistant\AssistantChat;
use App\Services\AiAssistant\AssistantLimiter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\OwnerApprovals;
use Modules\AppBuilder\Services\TelegramChat;
use Throwable;

/**
 * The site in Telegram, for a linked account: its invoices, its AI builder projects, and the site's AI assistant for
 * any question. Admins also get the owner's alerts here (sent by the AI builder). An unlinked chat is told to link
 * from the settings page; nothing about an account is shown before that.
 */
class SiteBot
{
    private const SHOWN = 5;

    private const MAX_QUESTION = 2000;

    public function __construct(
        private readonly TelegramApi $api,
        private readonly TelegramLinks $links,
        private readonly AssistantChat $assistant,
        private readonly AssistantLimiter $limiter,
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
        // the language of the person's Telegram (their phone), Arabic first
        app()->setLocale(str_starts_with((string) ($message['from']['language_code'] ?? 'ar'), 'en') ? 'en' : 'ar');

        if (str_starts_with($text, '/start')) {
            $this->start($chatId, trim(substr($text, strlen('/start'))));

            return;
        }
        $user = $this->links->userFor($chatId);
        if ($user === null) {
            $this->api->send($chatId, __('telegram.not_linked', ['url' => route('client.settings')]));

            return;
        }

        $this->answer($user, $chatId, $text, $message);
    }

    /** A button: talk to a project or start a new chat (any account), or an approval card (admins only). */
    private function pressed(array $button): void
    {
        $chatId = (string) ($button['message']['chat']['id'] ?? '');
        $user = $chatId !== '' ? $this->links->userFor($chatId) : null;
        $data = (string) ($button['data'] ?? '');
        $reply = match (true) {
            $user === null => __('telegram.not_linked', ['url' => route('client.settings')]),
            (bool) preg_match('/^ch:(\d{1,10})$/', $data, $match) => app(TelegramChat::class)->enter($user, $chatId, (int) $match[1]),
            $data === 'nw:0' => app(TelegramChat::class)->startNew($user, $chatId),
            $user->isAdmin() => app(OwnerApprovals::class)->act($chatId, $data),
            default => __('telegram.chat_not_yours'),
        };
        $this->api->answerButton((string) ($button['id'] ?? ''), mb_substr($reply, 0, 190));
        $this->api->send($chatId, $reply);
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
        match ($text) {
            __('telegram.btn_approvals') => $user->isAdmin() ? app(OwnerApprovals::class)->sendWaiting($chatId) : $this->ask($user, $chatId, $text),
            __('telegram.btn_invoices') => $this->invoices($user, $chatId),
            __('telegram.btn_projects') => $this->projects($user, $chatId),
            __('telegram.btn_support') => $this->toAssistant($user, $chatId),
            default => $this->talk($user, $chatId, $text, $message),
        };
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

    private function invoices(User $user, string $chatId): void
    {
        $invoices = Invoice::where('user_id', $user->id)->latest('id')->limit(self::SHOWN)->get();
        if ($invoices->isEmpty()) {
            $this->api->send($chatId, __('telegram.invoices_none'), menu: $this->menu($user));

            return;
        }

        $lines = $invoices->map(fn (Invoice $invoice) => __('telegram.invoice_line', [
            'id' => $invoice->id,
            'amount' => number_format((float) $invoice->total(true), 2).' '.($invoice->currency?->currency ?? ''),
            'status' => Lang::has('telegram.invoice_status.'.$invoice->status) ? __('telegram.invoice_status.'.$invoice->status) : $invoice->status,
        ]));
        $pay = $invoices->whereIn('status', ['unpaid', 'partially_paid'])->filter(fn (Invoice $invoice) => $invoice->uuid)
            ->map(fn (Invoice $invoice) => [__('telegram.pay', ['id' => $invoice->id]), route('billing.invoices.pay', $invoice->uuid)])->values()->all();
        $this->api->send($chatId, __('telegram.invoices_title')."\n\n".$lines->implode("\n"), [...$pay, [__('telegram.all_invoices'), route('billing.invoices.index')]]);
    }

    private function projects(User $user, string $chatId): void
    {
        $projects = BuilderProject::where('user_id', $user->id)->latest('id')->limit(self::SHOWN)->get();
        if ($projects->isEmpty()) {
            $this->api->send($chatId, __('telegram.projects_none', ['url' => url('/ai')]), actions: [[__('telegram.chat_new_button'), 'nw:0']]);

            return;
        }

        $lines = $projects->map(fn (BuilderProject $project) => __('telegram.project_line', [
            'title' => $project->title ?: __('telegram.untitled'),
            'status' => __('telegram.project_status.'.$project->status),
        ]));
        // each project's chat continues here in Telegram, or a new one starts
        $talk = $projects->map(fn (BuilderProject $project) => [__('telegram.chat_talk', ['title' => mb_substr($project->title ?: __('telegram.untitled'), 0, 30)]), 'ch:'.$project->id])->all();
        $this->api->send($chatId, __('telegram.projects_title')."\n\n".$lines->implode("\n")."\n\n".__('telegram.chat_how'), actions: [...$talk, [__('telegram.chat_new_button'), 'nw:0']]);
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

    /** @return list<string> an admin also gets the approvals */
    private function menu(?User $user = null): array
    {
        return [...($user?->isAdmin() ? [__('telegram.btn_approvals')] : []), __('telegram.btn_invoices'), __('telegram.btn_projects'), __('telegram.btn_support')];
    }
}
