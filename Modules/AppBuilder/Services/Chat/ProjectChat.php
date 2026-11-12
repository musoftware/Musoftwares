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
        // a paid project is an ongoing conversation; the limit only stops endless talk before a price
        if (! $project->takesRequests() && $project->messages()->where('role', BuilderMessage::USER)->count() >= (int) config('builder.limits.messages_per_project')) {
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
        // a project that started and was paid keeps a list of requests instead of one price
        $project->takesRequests() ? $this->addRequest($project, $turn, $question) : $this->updateQuote($project, $turn);

        return $project->messages()->create([
            'role' => BuilderMessage::ASSISTANT,
            'content' => $this->secrets->mask($project, $turn->reply),
            'payload' => $turn->payload(),
        ]);
    }

    /**
     * A new price, no price, or the shown price kept: a plain answer (which language, how hosting works) while a
     * price is shown must never take the price away.
     */
    private function updateQuote(BuilderProject $project, ChatTurn $turn): void
    {
        if (! $turn->quoteReady && $turn->keepQuote && $project->status === BuilderProject::QUOTED) {
            return;
        }
        // a customer's own project has no price before its paid inspection, whatever the model says
        if ($project->customer_project && $project->inspection_status !== \Modules\AppBuilder\Services\ProjectInspection::DONE) {
            return;
        }
        // a ready program to start from matters only for the first build; later builds start from the delivered one
        if (! $project->hasDelivery()) {
            $project->update(['starter' => app(\Modules\AppBuilder\Services\Starters::class)->find($turn->starter)['slug'] ?? null]);
        }

        $turn->quoteReady ? $this->applyQuote($project, $turn) : $this->withdrawQuote($project);
    }

    /** A message that asks for work becomes one request in the list; questions and small talk add nothing. */
    private function addRequest(BuilderProject $project, ChatTurn $turn, BuilderMessage $question): void
    {
        // a price shown before the list existed is closed: the list holds every price now
        $this->withdrawQuote($project);
        if ($turn->quoteReady) {
            app(\Modules\AppBuilder\Services\ProjectRequests::class)->add($project, $turn, $question);
        }
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
        $free = $turn->isFix && $project->isUnderWarranty();
        $price = match (true) {
            $free => $this->pricer->freeFix($turn->estimatedTokens, $turn->complex),
            // a change to what is delivered is priced by its own work, not as a new program of its type
            $project->hasDelivery() => $this->pricer->change($turn->estimatedTokens, $turn->complex),
            default => $this->pricer->price($turn->estimatedTokens, $turn->complex, $turn->designFee, $turn->projectType),
        };
        // a program we already sold keeps its known price: the same system is never cheaper than it was sold for
        if (! $free && ! $project->hasDelivery()) {
            $price['amount'] = max($price['amount'], app(\Modules\AppBuilder\Services\Starters::class)->knownPrice($project->starter));
        }
        $share = ! $free && $project->referrer_user_id ? $this->referral->addShare($price) : ['amount' => $price['amount'], 'commission' => 0.0];

        $project->update([
            'status' => BuilderProject::QUOTED,
            'title' => mb_substr($turn->title, 0, 255) ?: null,
            'spec' => $turn->spec,
            'estimated_tokens' => $price['tokens'],
            // an older study whose cost was put in the price stays there; a study bought on its own comes off the first price
            'amount' => max(1.0, $share['amount'] + ($project->study_status === \Modules\AppBuilder\Services\Study\FeasibilityStudy::READY ? (float) $project->study_fee : 0) - $project->studyCredit()),
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
    /**
     * The customer's own program, which Musoftware brought in: it counts as delivered (version 1), and its study (the
     * project's MUSOFTWARE.md) decides what is old, a free change that starts at once, and what is new and paid.
     */
    private function importedProject(BuilderProject $project): string
    {
        if (! $project->isImported()) {
            return '';
        }
        if ($project->customer_project) {
            return $this->customerProject($project);
        }

        return "\n\n## This is the customer's existing program\n\n"
            ."Musoftware brought this customer's own working program in, and the build engineer has all its files. Treat it as "
            ."already delivered. The study below (the project's MUSOFTWARE.md) lists what the program already has; use it, with "
            ."what the customer shows you, to tell a fix from something new. Never offer ready programs, designs, a download or "
            ."a live trial for it. Changes go live on the customer's own server.\n\n"
            .mb_substr((string) $project->import_profile, 0, 12000);
    }

    /**
     * A project the customer brought themselves (a Google Drive link). Until the paid inspection is done there is no
     * price to give; after it, the report and the study say what it has. We did not build it, so nothing is under warranty.
     */
    private function customerProject(BuilderProject $project): string
    {
        $inspected = $project->inspection_status === \Modules\AppBuilder\Services\ProjectInspection::DONE;
        $head = "\n\n## The customer brought their own project\n\n";
        if (! $inspected) {
            return $head."They sent a Google Drive link to a project of their own and said what they need. Before any price, the "
                ."project is inspected for a small fixed fee shown on the page (the inspection card). Do not quote anything now "
                ."(`quote.ready` false): answer their questions, and say the maintenance price shows after the inspection. What "
                ."they asked for:\n".mb_substr((string) $project->inspection_need, 0, 3000);
        }

        return $head."We inspected it; the study below says what it has. We did not build it, so there is no warranty: every fix "
            ."and every change is priced (`kind` \"fix\" for a fix, \"build\" for something new; both get a price). The finished "
            ."files are delivered as a download. Never offer ready programs, designs or a live trial for it.\n\n"
            .mb_substr((string) $project->import_profile, 0, 12000);
    }

    /**
     * A project that started and was paid: the customer writes like in a messaging app, during a build too, and each
     * change becomes one item in the list beside the chat.
     */
    private function requestList(BuilderProject $project): string
    {
        if (! $project->takesRequests()) {
            return '';
        }

        return "\n\n## The customer's list of requests\n\n"
            ."This project has started and is paid, so the customer writes here like in a messaging app, at any time, even "
            ."while the engineer works, and every change they ask for in the chat becomes one item in a list beside the chat. "
            ."For each message decide: when it asks for work and you know enough, send it as a quote (`quote.ready` true) for "
            ."that one request only, never one that is already in the list below. Ask first only when you truly cannot tell "
            ."what they want.\n\n"
            ."Fix or development, fairly:\n"
            ."- `kind` \"fix\" (تصليح): something the program already has, shows or promises does not work, or works differently "
            ."from what the customer means: a broken screen, a wrong result, a wording, a look, the steps of something it has. "
            ."A promise counts: when the program's own screens or texts already offer it (for example a subscription screen "
            ."that lists \"videos for subscribers only\"), making it really work is a fix, not development.\n"
            ."- `kind` \"build\" (تطوير): something the program does not have and never promised: a new feature, screen, report, "
            ."section of the admin panel, kind of user or integration.\n"
            ."- Mixed: send the fix now, and say the new part comes as its own item once they ask for it.\n\n"
            .$this->warrantyLine($project)."\n\n"
            ."Keep `spec` short: the engineer also reads the customer's own words. Answer in one or two lines, and always say "
            ."plainly which it is and why, in their language: for example \"ده تصليح لحاجة موجودة في التطبيق، مجاني ضمن الضمان، "
            ."وبدأنا عليه\" or \"ده تطوير جديد مش موجود في التطبيق، سعره في قائمة طلباتك على الجنب تدفعه وقت ما تحب\". Never say "
            ."a price is shown below the message. A question or a message that asks for no work gets `quote.ready` false and adds "
            ."nothing.\n\nThe list now:\n"
            .app(\Modules\AppBuilder\Services\ProjectRequests::class)->forPrompt($project);
    }

    /** The warranty as facts for the chat: fixes are free until it ends, then priced like any work. */
    private function warrantyLine(BuilderProject $project): string
    {
        $until = $project->warranty_until?->timezone('Africa/Cairo')->toDateString();
        if ($project->isUnderWarranty()) {
            return "Warranty: until {$until}. Every fix is free until then, however many, and goes to the engineer by itself. "
                .'Development always has its own price.';
        }

        return 'Warranty: '.($until ? "ended on {$until}" : 'none').'. A fix now has its own price too, like development; '
            .'say so kindly when it comes up.';
    }

    private function systemPrompt(BuilderProject $project): string
    {
        $prompt = (string) file_get_contents(module_path('AppBuilder', 'Resources/prompts/chat.md'));
        $state = (self::STATE_LINES[$project->status] ?? 'Unknown.').' '.$this->paymentFacts($project);

        // an imported project is already a working program: no ready programs or designs to offer it
        $starters = $project->isImported() ? '' : app(\Modules\AppBuilder\Services\Starters::class)->forPrompt();
        $designs = $project->isImported() ? '' : app(\Modules\AppBuilder\Services\Starters::class)->designsForPrompt();

        return $prompt
            .($starters !== '' ? "\n\n## Ready programs\n\n".$starters : '')
            .($designs !== '' ? "\n\n## Free designs\n\n".$designs : '')
            .$this->importedProject($project)
            .$this->requestList($project)
            ."\n\n## Where this project really is (from the site's records)\n\n".$state
            ."\n\n## Language of this answer\n\n".str_replace(':dialect', Dialect::arabicFor($project->country), self::LANGUAGE_RULES[$project->lastMessageLanguage()]);
    }
}
