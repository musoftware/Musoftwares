<?php

namespace Modules\AppBuilder\Services;

use Modules\AppBuilder\Models\BuilderEngineerQuestion;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * The chat runs on a small, fast model with short rules. When an answer depends on a fact it is not sure of, it
 * tells the customer it will ask the programmer and leaves the question here. The worker answers it with a stronger
 * model, its knowledge base and the web, and the chat brings the answer back to the customer. Every question is kept
 * (builder_engineer_questions), so the owner sees what the chat keeps missing.
 */
class EngineerQuestions
{
    /** A question taken this long ago with no answer goes back to the queue. */
    private const RETAKE_MINUTES = 10;

    /** After this long the customer has moved on; the question is no longer handed out. */
    private const FRESH_MINUTES = 30;

    private const CONTEXT_MESSAGES = 8;

    private const CONTEXT_CHARS = 1500;

    public function ask(BuilderProject $project, string $question): BuilderEngineerQuestion
    {
        return $project->engineerQuestions()->create(['question' => mb_substr(trim($question), 0, 2000), 'status' => BuilderEngineerQuestion::PENDING]);
    }

    public function isAsking(BuilderProject $project): bool
    {
        return $this->open()->where('project_id', $project->id)->exists();
    }

    /** @return list<array<string, mixed>> the questions for the worker now, marked as taken */
    public function forWorker(): array
    {
        $questions = $this->open()
            ->where(fn ($query) => $query->where('status', BuilderEngineerQuestion::PENDING)->orWhere('taken_at', '<', now()->subMinutes(self::RETAKE_MINUTES)))
            ->with('project')->oldest('id')->limit(5)->get();

        return $questions->map(function (BuilderEngineerQuestion $question): array {
            $question->update(['status' => BuilderEngineerQuestion::TAKEN, 'taken_at' => now()]);

            return ['id' => $question->id, 'question' => $question->question, 'title' => (string) $question->project->title, 'chat' => $this->context($question->project)];
        })->values()->all();
    }

    /** False when the question was already answered (the worker sent it twice). */
    public function answer(BuilderEngineerQuestion $question, string $answer): bool
    {
        $updated = BuilderEngineerQuestion::whereKey($question->id)->where('status', '!=', BuilderEngineerQuestion::ANSWERED)
            ->update(['answer' => mb_substr(trim($answer), 0, 6000), 'status' => BuilderEngineerQuestion::ANSWERED, 'answered_at' => now()]);

        return $updated > 0;
    }

    private function open()
    {
        return BuilderEngineerQuestion::whereIn('status', [BuilderEngineerQuestion::PENDING, BuilderEngineerQuestion::TAKEN])
            ->where('created_at', '>=', now()->subMinutes(self::FRESH_MINUTES));
    }

    /** The last lines of the chat, so the programmer knows what the customer is building. */
    private function context(BuilderProject $project): string
    {
        return $project->messages()->whereIn('role', [BuilderMessage::USER, BuilderMessage::ASSISTANT])->latest('id')->limit(self::CONTEXT_MESSAGES)->get()
            ->reverse()
            ->map(fn (BuilderMessage $message) => ($message->role === BuilderMessage::USER ? 'Customer: ' : 'Chat: ').mb_substr($message->content, 0, self::CONTEXT_CHARS))
            ->implode("\n\n");
    }
}
