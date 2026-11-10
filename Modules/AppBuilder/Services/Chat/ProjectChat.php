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
    ) {}

    /** @throws ChatLimitReached */
    public function start(string $ip, string $locale, ?int $referrerId = null, ?int $userId = null): BuilderProject
    {
        if (BuilderProject::startedTodayFrom($ip)->count() >= (int) config('builder.limits.projects_per_ip_per_day')) {
            throw new ChatLimitReached('Too many chats from this address today.');
        }

        return BuilderProject::create(['ip' => $ip, 'locale' => $locale, 'currency' => config('builder.pricing.currency'), 'referrer_user_id' => $referrerId, 'user_id' => $userId]);
    }

    /**
     * @param  list<UploadedFile>  $files
     *
     * @throws ChatUnavailable|ChatLimitReached
     */
    public function send(BuilderProject $project, string $text, array $files = []): BuilderMessage
    {
        if ($project->messages()->where('role', BuilderMessage::USER)->count() >= (int) config('builder.limits.messages_per_project')) {
            throw new ChatLimitReached('This chat reached its message limit.');
        }

        $question = $project->messages()->create(['role' => BuilderMessage::USER, 'content' => $text]);
        $this->attachments->attach($question, $files);

        // a shared link is looked at first, like a real programmer opens an example site before answering
        $links = $this->looks->linksIn($text);
        if ($links !== [] && $this->looks->workerIsWatching()) {
            $this->looks->askWorker($question, $links);

            return $question;
        }
        if ($links !== []) {
            $this->looks->readHere($question, $links);
        }

        return $this->answer($project, $question, forgetOnFailure: true);
    }

    /** The links were looked at (or the wait is over): the chat answers the message that had them, once. */
    public function continueAfterLook(BuilderProject $project): void
    {
        if (BuilderProject::whereKey($project->id)->where('awaiting_look', true)->update(['awaiting_look' => false]) === 0) {
            return;
        }

        $question = $project->messages()->where('role', BuilderMessage::USER)->latest('id')->firstOrFail();
        try {
            $this->answer($project->refresh(), $question, forgetOnFailure: false);
        } catch (ChatUnavailable) {
            // already logged; the customer sees their message without an answer and can write again
        }
    }

    /** @throws ChatUnavailable */
    private function answer(BuilderProject $project, BuilderMessage $question, bool $forgetOnFailure): BuilderMessage
    {
        set_time_limit(self::SECONDS_FOR_CLAUDE);

        try {
            $turn = $this->model->reply($this->systemPrompt(), $this->turns($project));
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

    private function systemPrompt(): string
    {
        return (string) file_get_contents(module_path('AppBuilder', 'Resources/prompts/chat.md'));
    }
}
