<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Chat\ChatTurn;
use Modules\AppBuilder\Services\Chat\ProjectChat;

/**
 * Quotes given while the builder was new and on trial were priced by token cost only. They move to the estimator's
 * price: the customer is told in the chat and by email, keeps the old price for 48 hours, then the new price applies.
 * Paid, delivered and free projects never change.
 */
class TrialPrices
{
    public const KEEP_OLD_PRICE_HOURS = 48;

    public function __construct(private readonly ScopeCounter $counter, private readonly ProjectChat $chat) {}

    /** @return Collection<int, BuilderProject> unpaid quotes for a new program that were not told about a new price yet */
    public function candidates(): Collection
    {
        return BuilderProject::query()
            ->where('status', BuilderProject::QUOTED)
            ->whereIn('project_type', array_keys((array) config('builder.estimator.platforms')))
            ->where('paid_total', '<=', 0)
            ->where('quote_paid', '<=', 0)
            ->where('free_build', false)
            ->where('free_fix', false)
            ->whereNull('trial_ends_at')
            ->whereNotNull('spec')
            ->orderBy('id')
            ->get()
            ->reject(fn (BuilderProject $project) => $project->hasDelivery() || $this->startsFromReadyProgram($project))
            ->values();
    }

    /** A ready program is priced only by the work on top of it, so counting its whole brief would overprice it. */
    private function startsFromReadyProgram(BuilderProject $project): bool
    {
        return filled($project->starter) && ! in_array($project->starter, app(Starters::class)->designSlugs(), true);
    }

    /** The price this project gets under the estimator. Counting its pages is saved, so it is asked only once. */
    public function newPrice(BuilderProject $project): float
    {
        $this->countScope($project);

        DB::beginTransaction();
        try {
            $this->requote($project);

            return (float) $project->refresh()->amount;
        } finally {
            DB::rollBack();
            $project->refresh();
        }
    }

    /** Tells the customer in the chat and by email; the old price stays payable for 48 hours. */
    public function announce(BuilderProject $project, float $newPrice): bool
    {
        if ($newPrice <= (float) $project->amount) {
            return false;
        }

        $project->update(['trial_ends_at' => now()->addHours(self::KEEP_OLD_PRICE_HOURS)]);
        $values = [
            'title' => $project->title ?: __('builder.follow_up.your_idea', [], $project->customerLanguage()),
            'old' => $this->money($project, (float) $project->amount),
            'new' => $this->money($project, $newPrice),
            'hours' => self::KEEP_OLD_PRICE_HOURS,
            'link' => route('builder.show', $project),
        ];
        $project->messages()->create(['role' => BuilderMessage::TEAM, 'content' => __('builder.trial_price.chat', $values, $project->customerLanguage())]);
        $this->mail($project, $values);

        return true;
    }

    /** @return int how many projects moved to their new price now that their 48 hours are over */
    public function applyDue(): int
    {
        $due = BuilderProject::query()->whereNotNull('trial_ends_at')->where('trial_ends_at', '<=', now())->get();
        $moved = 0;
        foreach ($due as $project) {
            $moved += $this->applyNewPrice($project);
        }

        return $moved;
    }

    private function applyNewPrice(BuilderProject $project): int
    {
        $stillOpen = $project->status === BuilderProject::QUOTED && (float) $project->paid_total <= 0 && (float) $project->quote_paid <= 0;
        if ($stillOpen) {
            $this->requote($project);
        }
        $project->update(['trial_ends_at' => null]);

        return $stillOpen ? 1 : 0;
    }

    private function countScope(BuilderProject $project): void
    {
        if ($project->price_scope !== null) {
            return;
        }

        $count = $this->counter->count((string) $project->project_type, (string) $project->spec);
        $project->update(['price_scope' => app(EstimatorPrice::class)->scope((string) $project->project_type, $count['pages'], $count['modules'])]);
    }

    /** The same quote path as the chat, from what the project already holds. */
    private function requote(BuilderProject $project): void
    {
        $scope = (array) $project->price_scope;
        $this->chat->requote($project, new ChatTurn(
            reply: '',
            needsTeam: false,
            quoteReady: true,
            title: (string) $project->title,
            spec: (string) $project->spec,
            estimatedTokens: (int) $project->estimated_tokens,
            complex: $project->build_model === config('builder.build_models.strong.model'),
            designFee: (float) $project->design_fee,
            projectType: (string) $project->project_type,
            pages: (int) ($scope['pages'] ?? 0),
            modules: array_column((array) ($scope['modules'] ?? []), 'key'),
        ));
    }

    private function mail(BuilderProject $project, array $values): void
    {
        $to = $project->client_email ?: $project->user?->email;
        if (! $to) {
            return;
        }

        $language = $project->customerLanguage();
        try {
            Mail::raw(__('builder.trial_price.body', $values, $language), fn ($mail) => $mail->to($to)->subject(__('builder.trial_price.subject', $values, $language)));
        } catch (\Throwable $e) {
            Log::warning('App builder: new price mail failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }
    }

    private function money(BuilderProject $project, float $amount): string
    {
        return number_format($amount, 2).' '.$project->currency;
    }
}
