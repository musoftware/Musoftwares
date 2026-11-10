<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * A running build that needs something (an answer, an access detail) asks here and waits.
 * The customer answers in the chat or the team answers from the admin page; the worker picks the answer up.
 */
class BuildQuestions
{
    public function __construct(private readonly ProjectPayment $payment, private readonly ProjectSecrets $secrets) {}

    public function ask(BuilderProject $project, string $question): BuilderMessage
    {
        $message = DB::transaction(function () use ($project, $question): BuilderMessage {
            $project->update(['awaiting_answer' => true]);

            return $project->messages()->create([
                'role' => BuilderMessage::ASSISTANT,
                'content' => $question,
                'payload' => ['question' => true],
            ]);
        });

        $this->tellCustomer($project, $question);
        app(BuilderPush::class)->notify($project, 'question');
        $this->payment->tellOwner($project, 'The build is waiting for an answer: '.$project->title.' - '.mb_substr($question, 0, 200));

        return $message;
    }

    /** A line from the customer or the team while the build waits. Access details in it are saved and masked. */
    public function answer(BuilderProject $project, string $text, string $role): BuilderMessage
    {
        return DB::transaction(function () use ($project, $text, $role): BuilderMessage {
            $project->update(['awaiting_answer' => false]);

            return $project->messages()->create(['role' => $role, 'content' => $this->secrets->mask($project, $text), 'payload' => ['answer' => true]]);
        });
    }

    /** @return list<array{id: int, from: string, text: string}> lines from the customer or the team after the given message */
    public function answersAfter(BuilderProject $project, int $messageId): array
    {
        return $project->messages()
            ->where('id', '>', $messageId)
            ->whereIn('role', [BuilderMessage::USER, BuilderMessage::TEAM])
            ->get()
            ->map(fn (BuilderMessage $message) => ['id' => $message->id, 'from' => $message->role === BuilderMessage::TEAM ? 'team' : 'customer', 'text' => $message->content])
            ->values()
            ->all();
    }

    /** The customer may have closed the page, so the question also goes to the email they gave before paying. */
    private function tellCustomer(BuilderProject $project, string $question): void
    {
        if (! $project->client_email) {
            return;
        }

        try {
            $body = __('builder.question_mail_body', ['question' => $question, 'link' => route('builder.show', $project)], $project->customerLanguage());
            Mail::raw($body, fn ($mail) => $mail->to($project->client_email)->subject(__('builder.question_mail_subject', [], $project->customerLanguage())));
        } catch (\Throwable $e) {
            Log::warning('App builder: question mail failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }
    }
}
