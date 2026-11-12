<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;
use Modules\AppBuilder\Services\Chat\ChatTurn;

/**
 * A project that started and was paid works like a messaging app: the customer writes whenever they like, and the
 * chat turns each change into a request in a list beside the chat. A change to what the program has is free and
 * waits only for the engineer; something new gets its own price and waits until the customer pays for it. The
 * engineer takes every ready request together as soon as the last build is done.
 */
class ProjectRequests
{
    /** The most requests paid in one payment; their ids ride in the gateway's order id. */
    public const MAX_PER_PAYMENT = 20;

    /** How many of the customer's lines before a request are read as its own words. */
    private const MAX_WORDS_MESSAGES = 6;

    public function __construct(private readonly QuotePricer $pricer, private readonly ProjectPayment $payment) {}

    /** The chat's brief for one request becomes an item in the list; a free one goes to the engineer at once. */
    public function add(BuilderProject $project, ChatTurn $turn, BuilderMessage $question): BuilderRequest
    {
        // a fix is free, however many, while the warranty lasts; something new is always priced
        $free = $turn->isFix && $project->isUnderWarranty();
        // priced by its own work, never by the minimum of the whole project's type
        $price = $free ? $this->pricer->freeFix($turn->estimatedTokens, $turn->complex) : $this->pricer->change($turn->estimatedTokens, $turn->complex);

        $request = $project->requests()->create([
            'message_id' => $question->id,
            'title' => mb_substr($turn->title, 0, 255) ?: mb_substr($question->content, 0, 80),
            'spec' => $turn->spec,
            'customer_words' => $this->customerWords($project, $question),
            'kind' => $turn->isFix ? BuilderRequest::FIX : BuilderRequest::BUILD,
            'free' => $free,
            'complex' => $turn->complex,
            'estimated_tokens' => $price['tokens'],
            'amount' => $price['amount'],
            'budget_usd' => $price['budget'],
            'model' => $price['model'],
            'status' => $free ? BuilderRequest::READY : BuilderRequest::UNPAID,
        ]);
        if ($free) {
            $project->increment('free_fixes_used');
            $this->payment->tellOwner($project, 'Free change added: '.$request->title);
            $this->startNext($project);
        }

        return $request;
    }

    /** The customer's lines since the previous request, as they wrote them. */
    private function customerWords(BuilderProject $project, BuilderMessage $question): string
    {
        $after = (int) $project->requests()->whereNotNull('message_id')->max('message_id');

        return $project->messages()
            ->reorder('id', 'desc')
            ->where('role', BuilderMessage::USER)
            ->where('id', '>', $after)
            ->where('id', '<=', $question->id)
            ->limit(self::MAX_WORDS_MESSAGES)
            ->pluck('content')
            ->reverse()
            ->map(fn ($text) => trim((string) $text))
            ->filter()
            ->unique()
            ->implode("\n\n");
    }

    /** @param list<int> $ids */
    public function unpaid(BuilderProject $project, array $ids): Collection
    {
        return $project->requests()->where('status', BuilderRequest::UNPAID)->whereIn('id', $ids)->limit(self::MAX_PER_PAYMENT)->get();
    }

    /**
     * The chosen requests were paid: they wait for the engineer and the project's total grows. The gateway may report
     * the same payment twice, so only requests that are still unpaid are counted.
     *
     * @param  list<int>  $ids
     */
    public function markPaid(BuilderProject $project, array $ids, ?string $reference): int
    {
        $paid = DB::transaction(function () use ($project, $ids, $reference): Collection {
            $requests = $project->requests()->where('status', BuilderRequest::UNPAID)->whereIn('id', $ids)->lockForUpdate()->get();
            $requests->each->update(['status' => BuilderRequest::READY, 'paid_at' => now(), 'payment_reference' => $reference]);
            $project->update(['paid_total' => (float) $project->paid_total + (float) $requests->sum('amount')]);

            return $requests;
        });
        if ($paid->isEmpty()) {
            Log::warning('App builder: payment for requests that are not open', ['project_id' => $project->id, 'ids' => $ids, 'reference' => $reference]);

            return 0;
        }

        $this->payment->tellOwner($project, 'Paid requests: '.$project->title.' ('.$paid->pluck('title')->implode(', ').')');
        app(ProjectAccount::class)->linkAfterFee($project);
        app(PaymentInvoices::class)->requestsPaid($project->refresh(), $paid);
        $this->startNext($project->refresh());

        return $paid->count();
    }

    /**
     * When the engineer is free, every ready request goes to it as one build: the budgets add up, and the strongest
     * model any of them needs does the work. Nothing starts while a build, an import or a release runs.
     */
    public function startNext(BuilderProject $project): bool
    {
        $project->refresh();
        if ($project->status !== BuilderProject::READY || ($project->job_kind ?: BuilderProject::JOB_BUILD) !== BuilderProject::JOB_BUILD) {
            return false;
        }
        // the customer asked to put the changes live while the last build ran: that goes first
        if ($project->release_asked_at !== null && app(ProjectImports::class)->queueRelease($project)) {
            return true;
        }
        $ready = $project->requests()->where('status', BuilderRequest::READY)->get();
        if ($ready->isEmpty()) {
            return false;
        }

        DB::transaction(function () use ($project, $ready): void {
            $ready->each->update(['status' => BuilderRequest::BUILDING]);
            $project->update([
                'status' => BuilderProject::QUEUED,
                'job_kind' => BuilderProject::JOB_BUILD,
                'paid_at' => now(),
                'attempts' => 0,
                'error' => null,
                'progress_step' => null,
                'progress_file' => null,
                'progress_percent' => 0,
                'progress_note' => null,
                'job_cost_usd' => 0,
                'budget_usd' => round((float) $ready->sum('budget_usd'), 4),
                'build_model' => $this->modelFor($ready),
                'estimated_tokens' => (int) $ready->sum('estimated_tokens'),
                'free_fix' => $ready->every(fn (BuilderRequest $request) => $request->free),
                // each request was priced on its own; the old quote is never settled again by this build
                'quote_paid' => 0,
                'payment_due' => 0,
            ]);
        });

        return true;
    }

    /** @param Collection<int, BuilderRequest> $requests */
    private function modelFor(Collection $requests): string
    {
        $strong = $requests->contains(fn (BuilderRequest $request) => $request->complex);

        return (string) config('builder.build_models.'.($strong ? 'strong' : 'standard').'.model');
    }

    /** The customer takes back a request the engineer has not started: one waiting for payment, or one in line. */
    public function cancel(BuilderProject $project, int $id): bool
    {
        return $project->requests()->whereKey($id)->whereIn('status', [BuilderRequest::UNPAID, BuilderRequest::READY])->first()?->delete() ?? false;
    }

    /** Every project with a request waiting for a free engineer; run every minute in case a start was missed. */
    public function startAllWaiting(): int
    {
        return BuilderProject::query()
            ->where('status', BuilderProject::READY)
            ->where(fn ($query) => $query->whereNotNull('release_asked_at')->orWhereHas('requests', fn ($requests) => $requests->where('status', BuilderRequest::READY)))
            ->get()
            ->filter(fn (BuilderProject $project) => $this->startNext($project))
            ->count();
    }

    /** The build that carried these requests was delivered: they are done, in this version. */
    public function finish(BuilderProject $project): void
    {
        $project->requests()->where('status', BuilderRequest::BUILDING)->update(['status' => BuilderRequest::DONE, 'built_in_version' => $project->version]);
        $this->startNext($project);
    }

    /**
     * What the engineer works from: the requests in order, each in the customer's own words (like a client writing to
     * their developer) with the chat's short note. Null when the build carries no requests.
     */
    public function briefFor(BuilderProject $project): ?string
    {
        $building = $project->requests()->where('status', BuilderRequest::BUILDING)->get();
        if ($building->isEmpty()) {
            return null;
        }

        return "The customer asked for these changes, in this order. Make every one of them.\n\n"
            .$building->values()->map(fn (BuilderRequest $request, int $index) => '### '.($index + 1).'. '.$request->title
                ."\n\nThe customer's own words:\n\n".($request->customer_words ?: '(see the note)')
                ."\n\nNote from the chat:\n\n".trim($request->spec))->implode("\n\n");
    }

    /** The list for the chat's prompt, so a request already there is never added twice. */
    public function forPrompt(BuilderProject $project): string
    {
        $lines = $project->requests()->latest('id')->limit(30)->get()->reverse()
            ->map(fn (BuilderRequest $request) => '- ['.$request->status.', '.($request->free ? 'free' : number_format((float) $request->amount, 2).' '.$project->currency).'] '.$request->title);

        return $lines->isEmpty() ? '(empty)' : $lines->implode("\n");
    }
}
