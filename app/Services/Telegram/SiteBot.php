<?php

namespace App\Services\Telegram;

use App\Models\Invoice;
use App\Models\User;
use App\Services\AiAssistant\AssistantChat;
use App\Services\AiAssistant\AssistantLimiter;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\OwnerApprovals;
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
        $text = trim((string) ($message['text'] ?? ''));
        if ($chatId === '' || ($message['chat']['type'] ?? '') !== 'private' || $text === '') {
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

        $this->answer($user, $chatId, $text);
    }

    /** A button on a card (only admins get cards with buttons): approve, turn down, price, free. */
    private function pressed(array $button): void
    {
        $chatId = (string) ($button['message']['chat']['id'] ?? '');
        $admin = $chatId !== '' ? $this->links->userFor($chatId) : null;
        $reply = $admin?->isAdmin() ? app(OwnerApprovals::class)->act($chatId, (string) ($button['data'] ?? '')) : __('telegram.not_linked', ['url' => route('client.settings')]);
        $this->api->answerButton((string) ($button['id'] ?? ''), $reply);
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

    private function answer(User $user, string $chatId, string $text): void
    {
        // a price the owner was asked for on an approval card
        if ($user->isAdmin() && app(OwnerApprovals::class)->priceTyped($chatId, $text)) {
            return;
        }
        match ($text) {
            __('telegram.btn_approvals') => $user->isAdmin() ? app(OwnerApprovals::class)->sendWaiting($chatId) : $this->ask($user, $chatId, $text),
            __('telegram.btn_invoices') => $this->invoices($user, $chatId),
            __('telegram.btn_projects') => $this->projects($user, $chatId),
            __('telegram.btn_support') => $this->api->send($chatId, __('telegram.support_prompt'), menu: $this->menu($user)),
            default => $this->ask($user, $chatId, $text),
        };
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
            $this->api->send($chatId, __('telegram.projects_none', ['url' => url('/ai')]), menu: $this->menu($user));

            return;
        }

        $lines = $projects->map(fn (BuilderProject $project) => __('telegram.project_line', [
            'title' => $project->title ?: __('telegram.untitled'),
            'status' => __('telegram.project_status.'.$project->status),
        ]));
        $open = $projects->map(fn (BuilderProject $project) => [__('telegram.open', ['title' => mb_substr($project->title ?: __('telegram.untitled'), 0, 40)]), route('builder.show', $project)])->all();
        $this->api->send($chatId, __('telegram.projects_title')."\n\n".$lines->implode("\n"), $open);
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
