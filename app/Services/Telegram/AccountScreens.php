<?php

namespace App\Services\Telegram;

use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Lang;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * What a linked account sees in the Telegram bot: invoices, AI builder projects with their progress, balance and
 * points, the invite link, support tickets, and the bot's own settings. Each screen is one message.
 */
class AccountScreens
{
    private const SHOWN = 5;

    public function __construct(private readonly TelegramApi $api) {}

    /** @param  list<string>  $menu */
    public function invoices(User $user, string $chatId, array $menu): void
    {
        $invoices = Invoice::where('user_id', $user->id)->latest('id')->limit(self::SHOWN)->get();
        if ($invoices->isEmpty()) {
            $this->api->send($chatId, __('telegram.invoices_none'), menu: $menu);

            return;
        }

        $lines = $invoices->map(fn (Invoice $invoice) => __('telegram.invoice_line', [
            'id' => $invoice->id,
            'amount' => self::amount($invoice),
            'status' => self::label('telegram.invoice_status', (string) $invoice->status),
        ]));
        $pay = $invoices->whereIn('status', ['unpaid', 'partially_paid'])->filter(fn (Invoice $invoice) => $invoice->uuid)
            ->map(fn (Invoice $invoice) => self::payLink($invoice))->values()->all();
        $this->api->send($chatId, __('telegram.invoices_title')."\n\n".$lines->implode("\n"), [...$pay, [__('telegram.all_invoices'), route('billing.invoices.index')]]);
    }

    /** Each project with how far it is; talk to it here, pay its open price, or open what was delivered. */
    public function projects(User $user, string $chatId): void
    {
        $projects = BuilderProject::where('user_id', $user->id)->latest('id')->limit(self::SHOWN)->get();
        if ($projects->isEmpty()) {
            $this->api->send($chatId, __('telegram.projects_none', ['url' => url('/ai')]), actions: [[__('telegram.chat_new_button'), 'nw:0']]);

            return;
        }

        $lines = $projects->map(fn (BuilderProject $project) => $this->projectLine($project));
        $talk = $projects->map(fn (BuilderProject $project) => [__('telegram.chat_talk', ['title' => self::short($project)]), 'ch:'.$project->id])->all();
        $links = $projects->flatMap(fn (BuilderProject $project) => $this->projectLinks($project))->values()->all();
        $this->api->send($chatId, __('telegram.projects_title')."\n\n".$lines->implode("\n\n")."\n\n".__('telegram.chat_how'), $links, actions: [...$talk, [__('telegram.chat_new_button'), 'nw:0']]);
    }

    /** @param  list<string>  $menu */
    public function balance(User $user, string $chatId, array $menu): void
    {
        $this->api->send($chatId, __('telegram.balance', [
            'balance' => number_format((float) $user->balance(), 2).' '.($user->currency_name() ?? ''),
            'points' => number_format((int) $user->loyalty_points_balance),
        ]), [[__('telegram.balance_open'), route('loyalty.index')]], $menu);
    }

    /** The account's own invite links, and what they earned so far. */
    public function invite(User $user, string $chatId): void
    {
        $referral = app(ReferralService::class)->ensureReferralSystemActive($user);
        $code = $referral->slug ?: $referral->key;
        $this->api->send($chatId, __('telegram.invite', [
            'site' => url('/r/'.$code),
            'ai' => url('/r/'.$code.'/ai'),
            'share' => (int) round((float) config('builder.referral.share') * 100),
            'earned' => number_format((float) $user->pending_commission, 2).' '.($user->currency_name() ?? ''),
        ]), [[__('telegram.invite_open'), route('referrals.index')]]);
    }

    public function tickets(User $user, string $chatId): void
    {
        $tickets = Ticket::where('user_id', $user->id)->latest('id')->limit(self::SHOWN)->get();
        $new = [__('telegram.ticket_new'), route('tickets.create')];
        if ($tickets->isEmpty()) {
            $this->api->send($chatId, __('telegram.tickets_none'), [$new]);

            return;
        }

        $lines = $tickets->map(fn (Ticket $ticket) => __('telegram.ticket_line', [
            'id' => $ticket->id,
            'subject' => mb_substr((string) $ticket->ticket_subject, 0, 60),
            'status' => self::label('telegram.ticket_status', (string) $ticket->ticket_status),
        ]));
        $open = $tickets->map(fn (Ticket $ticket) => [__('telegram.ticket_open', ['id' => $ticket->id]), route('tickets.show', $ticket->id)])->all();
        $this->api->send($chatId, __('telegram.tickets_title')."\n\n".$lines->implode("\n"), [...$open, $new]);
    }

    /** Alerts on or off, and the bot's language. */
    public function settings(User $user, string $chatId): void
    {
        $alerts = $user->telegram_alerts_off ? __('telegram.alerts_turn_on') : __('telegram.alerts_turn_off');
        $language = CustomerAlerts::localeOf($user) === 'ar' ? ['English', 'lg:en'] : ['عربي', 'lg:ar'];
        $state = $user->telegram_alerts_off ? __('telegram.alerts_are_off') : __('telegram.alerts_are_on');
        $this->api->send($chatId, __('telegram.settings_title')."\n\n".$state, actions: [[$alerts, 'al:t'], $language]);
    }

    /** @return array{0: string, 1: string} */
    public static function payLink(Invoice $invoice): array
    {
        return [__('telegram.pay', ['id' => $invoice->id]), route('billing.invoices.pay', $invoice->uuid)];
    }

    public static function amount(Invoice $invoice): string
    {
        return number_format((float) $invoice->total(true), 2).' '.($invoice->currency?->currency ?? '');
    }

    private function projectLine(BuilderProject $project): string
    {
        $line = __('telegram.project_line', ['title' => $project->title ?: __('telegram.untitled'), 'status' => __('telegram.project_status.'.$project->status)]);
        if ($project->status !== BuilderProject::BUILDING) {
            return $line;
        }
        // how far the build is, and what it is doing now
        $items = $project->progress_phases['items'] ?? [];
        $now = collect($items)->firstWhere('done', false)['title'] ?? $project->progress_note;

        return $line.' · '.(int) $project->progress_percent.'%'.($now ? "\n".__('telegram.project_now', ['what' => $now]) : '');
    }

    /** @return list<array{0: string, 1: string}> */
    private function projectLinks(BuilderProject $project): array
    {
        $page = route('builder.show', $project);
        if ($project->status === BuilderProject::QUOTED || $project->hasBalanceDue()) {
            return [[__('telegram.project_pay', ['title' => self::short($project)]), $page]];
        }
        if (! $project->hasDelivery()) {
            return [];
        }
        $download = ! $project->isOwnerImport() ? [[__('telegram.project_download', ['title' => self::short($project)]), route('builder.download', $project)]] : [];

        return [[__('telegram.project_open', ['title' => self::short($project)]), $page], ...$download];
    }

    private static function short(BuilderProject $project): string
    {
        return mb_substr($project->title ?: __('telegram.untitled'), 0, 30);
    }

    private static function label(string $key, string $status): string
    {
        return Lang::has($key.'.'.$status) ? __($key.'.'.$status) : $status;
    }
}
