<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * A customer who talked with us and went quiet before paying gets two emails, once each: after two days a reminder
 * of their chat and project, after a week the discount on their price (the same one offered when leaving the
 * price), or a second reminder when there is no price yet. Only customers we have an email for.
 */
class ChatFollowUp
{
    private const RECENT_DAYS = 30;

    public function __construct(private readonly LeaveDiscount $discount) {}

    /** @return int how many emails went out */
    public function sendDue(): int
    {
        $sent = 0;
        foreach ($this->quiet('follow_up_at', (int) config('builder.follow_up.remind_after_days'))->get() as $project) {
            $sent += $this->remind($project);
        }
        $offerDays = (int) config('builder.follow_up.offer_after_days');
        $gap = max(1, $offerDays - (int) config('builder.follow_up.remind_after_days'));
        // the discount comes days after the reminder, never in the same hour for an old chat
        foreach ($this->quiet('follow_up_offer_at', $offerDays)->where('follow_up_at', '<=', now()->subDays($gap))->get() as $project) {
            $sent += $this->offer($project);
        }

        return $sent;
    }

    /** Unpaid chats with an email whose last line is older than the given days, not followed up this way yet. */
    private function quiet(string $column, int $days): Builder
    {
        return BuilderProject::query()
            ->whereIn('status', [BuilderProject::CHATTING, BuilderProject::QUOTED])
            // recent chats only, so old ones are never mailed all at once
            ->where('created_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->where('paid_total', '<=', 0)
            ->whereNull($column)
            ->where(fn (Builder $who) => $who->whereNotNull('client_email')->orWhereNotNull('user_id'))
            ->whereHas('messages', fn (Builder $any) => $any->where('role', BuilderMessage::USER))
            ->whereDoesntHave('messages', fn (Builder $recent) => $recent->where('created_at', '>', now()->subDays($days)));
    }

    private function remind(BuilderProject $project): int
    {
        $project->update(['follow_up_at' => now()]);

        return $this->send($project, 'builder.follow_up.remind_subject', 'builder.follow_up.remind_body', []);
    }

    /** The discount when there is an open price and it was not taken yet; otherwise a second, plain reminder. */
    private function offer(BuilderProject $project): int
    {
        $project->update(['follow_up_offer_at' => now()]);
        $before = (float) $project->amount;
        if ($this->discount->take($project)) {
            $project->refresh();

            return $this->send($project, 'builder.follow_up.offer_subject', 'builder.follow_up.offer_body', [
                'percent' => $project->leave_discount_percent,
                'before' => number_format($before, 2).' '.$project->currency,
                'price' => number_format((float) $project->amount, 2).' '.$project->currency,
            ]);
        }

        return $this->send($project, 'builder.follow_up.again_subject', 'builder.follow_up.again_body', []);
    }

    private function send(BuilderProject $project, string $subject, string $body, array $values): int
    {
        $to = $project->client_email ?: $project->user?->email;
        $language = $project->customerLanguage();
        $values += ['title' => $project->title ?: __('builder.follow_up.your_idea', [], $language), 'link' => route('builder.show', $project)];

        try {
            Mail::raw(__($body, $values, $language), fn ($mail) => $mail->to($to)->subject(__($subject, $values, $language)));

            return 1;
        } catch (\Throwable $e) {
            Log::warning('App builder: follow-up mail failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);

            return 0;
        }
    }
}
