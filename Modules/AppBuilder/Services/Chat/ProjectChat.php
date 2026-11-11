<?php

namespace Modules\AppBuilder\Services\Chat;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;
use Illuminate\Http\UploadedFile;
use Modules\AppBuilder\Models\BuilderSecret;
use Modules\AppBuilder\Services\AttachmentStore;
use Modules\AppBuilder\Services\BuilderReferral;
use Modules\AppBuilder\Services\ProjectSecrets;
use Modules\AppBuilder\Services\QuotePricer;

/** One customer message in, one Claude answer out, with the quote kept on the project. */
class ProjectChat
{
    private const SECONDS_FOR_CLAUDE = 240;

    public function __construct(
        private readonly ChatModel $model,
        private readonly QuotePricer $pricer,
        private readonly AttachmentStore $attachments,
        private readonly ProjectSecrets $secrets,
        private readonly BuilderReferral $referral,
        private readonly \Modules\AppBuilder\Services\Study\FeasibilityStudy $study,
        private readonly \Modules\AppBuilder\Services\LinkLooks $looks,
        private readonly \Modules\AppBuilder\Services\LeaveDiscount $leaveDiscount,
        private readonly FreeChat $freeChat,
    ) {}

    /** @throws ChatLimitReached */
    public function start(string $ip, string $locale, ?int $referrerId = null, ?int $userId = null, bool $unlimited = false, ?string $userAgent = null): BuilderProject
    {
        if (! $unlimited && BuilderProject::startedTodayFrom($ip)->count() >= (int) config('builder.limits.projects_per_ip_per_day')) {
            throw new ChatLimitReached('Too many chats from this address today.');
        }

        return BuilderProject::create(['ip' => $ip, 'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,'locale' => $locale, 'currency' => config('builder.pricing.currency'), 'referrer_user_id' => $referrerId, 'user_id' => $userId]);
    }

    /**
     * @param  list<UploadedFile>  $files
     * @param  list<string>  $previewNames  pictures the page made smaller before sending
     *
     * @throws ChatUnavailable|ChatLimitReached
     */
    public function send(BuilderProject $project, string $text, array $files = [], array $previewNames = []): BuilderMessage
    {
        if ($project->messages()->where('role', BuilderMessage::USER)->count() >= (int) config('builder.limits.messages_per_project')) {
            throw new ChatLimitReached('This chat reached its message limit.');
        }
        if ($this->freeChat->mustSignIn($project)) {
            throw new SignInNeeded('A guest reached the free messages without a price.');
        }

        $question = $project->messages()->create(['role' => BuilderMessage::USER, 'content' => $text]);
        $this->attachments->attach($question, $files, $previewNames);

        // links and spreadsheets are looked at first, like a real programmer opens them before answering
        $shared = $this->looks->sharedIn($question);
        if ($shared !== [] && $this->looks->workerIsWatching()) {
            $this->looks->askWorker($question, $shared);

            return $question;
        }
        if ($shared !== []) {
            $this->looks->readHere($question, $shared);
        }

        return $this->answer($project, $question, forgetOnFailure: true);
    }

    /** The links were looked at (or the wait is over): the chat answers the message that had them, once. */
    public function continueAfterLook(BuilderProject $project): void
    {
        if (BuilderProject::whereKey($project->id)->where('awaiting_look', true)->update(['awaiting_look' => false]) === 0) {
            return;
        }

        $question = $project->lastCustomerMessage() ?? throw new \RuntimeException('A look finished on a project without a customer message.');
        try {
            $this->answer($project->refresh(), $question, forgetOnFailure: false);
        } catch (ChatUnavailable) {
            // already logged; the customer sees their message without an answer and can write again
        }
    }

    /** The free model for a guest's first messages, Claude for everyone else and whenever the free one fails. */
    private function reply(BuilderProject $project, string $system, array $turns): ChatTurn
    {
        if ($this->freeChat->applies($project)) {
            try {
                return $this->freeChat->model()->reply($system, $turns);
            } catch (ChatUnavailable $exception) {
                Log::info('App builder: free chat unavailable, Claude answers', ['project_id' => $project->id, 'error' => $exception->getMessage()]);
            }
        }

        return $this->model->reply($system, $turns);
    }

    /** @throws ChatUnavailable */
    private function answer(BuilderProject $project, BuilderMessage $question, bool $forgetOnFailure): BuilderMessage
    {
        set_time_limit(self::SECONDS_FOR_CLAUDE);

        try {
            $turn = $this->reply($project, $this->systemPrompt($project), $this->turns($project));
        } catch (ChatUnavailable $exception) {
            Log::warning('App builder: chat turn failed', ['project_id' => $project->id, 'error' => $exception->getMessage()]);
            if ($forgetOnFailure) {
                // drop the unanswered line so the customer can send it again without a duplicate
                $this->attachments->forget($question);
                $question->forceDelete();
            }

            throw $exception;
        }

        return DB::transaction(fn () => $this->record($project, $turn, $question));
    }

    private function record(BuilderProject $project, ChatTurn $turn, BuilderMessage $question): BuilderMessage
    {
        $this->keepSecretsApart($project, $turn, $question);

        $project->increment('chat_input_tokens', $turn->inputTokens);
        $project->increment('chat_output_tokens', $turn->outputTokens);
        // a ready program to start from matters only for the first build; later builds start from the delivered one
        if (! $project->hasDelivery()) {
            $project->update(['starter' => app(\Modules\AppBuilder\Services\Starters::class)->find($turn->starter)['slug'] ?? null]);
        }

        $turn->quoteReady ? $this->applyQuote($project, $turn) : $this->withdrawQuote($project);
        if ($turn->quoteReady && $this->study->isDue($project->refresh())) {
            $this->study->start($project);
        }

        return $project->messages()->create([
            'role' => BuilderMessage::ASSISTANT,
            'content' => $this->secrets->mask($project, $turn->reply),
            'payload' => $turn->payload(),
        ]);
    }

    /** Access details typed in the chat are saved encrypted, and the chat keeps only a mask in their place. */
    private function keepSecretsApart(BuilderProject $project, ChatTurn $turn, BuilderMessage $question): void
    {
        if ($turn->secrets === []) {
            return;
        }

        $this->secrets->save($project, $turn->secrets, BuilderSecret::FROM_CUSTOMER);
        $question->update(['content' => $this->secrets->mask($project, $question->content)]);
    }

    private function applyQuote(BuilderProject $project, ChatTurn $turn): void
    {
        $free = $turn->isFix && $project->canGetFreeFix($turn->estimatedTokens);
        $price = $free
            ? $this->pricer->freeFix($turn->estimatedTokens, $turn->complex)
            : $this->pricer->price($turn->estimatedTokens, $turn->complex, $turn->designFee, $turn->projectType);
        $share = ! $free && $project->referrer_user_id ? $this->referral->addShare($price) : ['amount' => $price['amount'], 'commission' => 0.0];

        $project->update([
            'status' => BuilderProject::QUOTED,
            'title' => mb_substr($turn->title, 0, 255) ?: null,
            'spec' => $turn->spec,
            'estimated_tokens' => $price['tokens'],
            // a feasibility study already written stays part of the price when the scope changes
            'amount' => $share['amount'] + ($project->study_status === \Modules\AppBuilder\Services\Study\FeasibilityStudy::READY ? (float) $project->study_fee : 0),
            'project_type' => $price['project_type'],
            'referral_commission' => $share['commission'],
            'design_fee' => $price['design_fee'] ?? 0,
            'budget_usd' => $price['budget'],
            'build_model' => $price['model'],
            'free_fix' => $free,
            // a new quote starts its own payments
            'quote_paid' => 0,
            'payment_due' => 0,
            'quote_version' => $project->quote_version + 1,
        ]);
        // a discount the customer already took stays with the project
        $this->leaveDiscount->applyTo($project);
        // the top of the range is the most that can be asked for later; a free fix has no range
        $project->update(['price_max' => $free ? 0 : \Modules\AppBuilder\Services\PriceRange::around((float) $project->amount)['to']]);
    }

    /** The customer is changing the scope, so the old price must not stay payable. */
    private function withdrawQuote(BuilderProject $project): void
    {
        if ($project->status === BuilderProject::QUOTED) {
            $project->update(['status' => $project->hasDelivery() ? BuilderProject::READY : BuilderProject::CHATTING, 'free_fix' => false]);
        }
    }

    /** @return list<array{role: string, content: string|list<array<string, mixed>>}> */
    private function turns(BuilderProject $project): array
    {
        return $project->messages()->with('attachments')->get()
            ->map(fn (BuilderMessage $message) => ['role' => $message->modelRole(), 'content' => $this->attachments->contentFor($message)])
            ->values()
            ->all();
    }

    private const LANGUAGE_RULES = [
        'ar' => 'The customer\'s last message is in Arabic: write this answer in :dialect, the way people speak where the customer is, even though the examples above are in Egyptian Arabic. If the customer clearly writes in another Arabic dialect, follow theirs.',
        'en' => 'The customer\'s last message is in English: write this whole answer in English, with no Arabic words, even though the examples above are in Arabic.',
    ];

    /** The real state from the site's records; what the customer says about paying never changes it. */
    private const STATE_LINES = [
        BuilderProject::CHATTING => 'No price is open and nothing is paid.',
        BuilderProject::QUOTED => 'A price is shown on the page and it is NOT paid. The build has not started.',
        BuilderProject::DESIGNING => 'Paid. Waiting for the designer before the build starts.',
        BuilderProject::QUEUED => 'Paid. The build is in the queue and starts soon.',
        BuilderProject::BUILDING => 'Paid. The engineer is building it now; progress shows on the page.',
        BuilderProject::READY => 'Paid and delivered; the program is on the page.',
        BuilderProject::FAILED => 'Paid. The build stopped on a problem and the team was told; it will continue.',
    ];

    /** Read-only numbers for the chat: it can tell them to the customer but never change them. */
    private function paymentFacts(BuilderProject $project): string
    {
        $money = fn (float $value) => number_format($value, 2).' '.$project->currency;
        $facts = 'Paid in total on this project: '.$money((float) $project->paid_total).'.';
        if ($project->status === BuilderProject::QUOTED) {
            $facts .= ' Open price: '.$money((float) $project->amount).'.';
        }
        if ($project->hasBalanceDue()) {
            $facts .= ' Still to pay before the files are handed over: '.$money($project->balanceDue()).'.';
        }

        return $facts;
    }

    /** The prompt, the project's real state, and the language this answer must be in. */
    private function systemPrompt(BuilderProject $project): string
    {
        $prompt = (string) file_get_contents(module_path('AppBuilder', 'Resources/prompts/chat.md'));
        $state = (self::STATE_LINES[$project->status] ?? 'Unknown.').' '.$this->paymentFacts($project);

        $starters = app(\Modules\AppBuilder\Services\Starters::class)->forPrompt();

        return $prompt
            .($starters !== '' ? "\n\n## Ready programs\n\n".$starters : '')
            ."\n\n## Where this project really is (from the site's records)\n\n".$state
            ."\n\n## Language of this answer\n\n".str_replace(':dialect', Dialect::arabicFor($project->country), self::LANGUAGE_RULES[$project->lastMessageLanguage()]);
    }
}
