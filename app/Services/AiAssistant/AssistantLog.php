<?php

namespace App\Services\AiAssistant;

use App\Models\AssistantExchange;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Keeps what visitors ask the site's AI assistant and what it answers, so the owner can read the conversations.
 * A failed write is logged and never costs the visitor their answer.
 */
class AssistantLog
{
    private const PER_PAGE = 30;

    public function record(string $visitor, ?User $user, ?string $ip, ?string $page, string $question, string $answer): void
    {
        try {
            AssistantExchange::create([
                'visitor' => mb_substr($visitor, 0, 80),
                'user_id' => $user?->id,
                'ip' => $ip,
                'page' => $page === null ? null : mb_substr($page, 0, 255),
                'question' => $question,
                'answer' => $answer,
            ]);
        } catch (Throwable $error) {
            Log::warning('AI assistant: the conversation was not saved', ['visitor' => $visitor, 'error' => $error->getMessage()]);
        }
    }

    /** One row per visitor, the one who wrote last first. */
    public function visitors(): LengthAwarePaginator
    {
        $page = AssistantExchange::query()
            ->select('visitor', DB::raw('count(*) as questions'), DB::raw('max(id) as last_id'))
            ->groupBy('visitor')
            ->orderByDesc('last_id')
            ->paginate(self::PER_PAGE);
        $last = AssistantExchange::with('user:id,name,email')->whereIn('id', $page->pluck('last_id'))->get()->keyBy('id');

        return $page->through(fn (AssistantExchange $row) => [
            'visitor' => $row->visitor,
            'questions' => (int) $row->questions,
        ] + $this->summary($last[$row->last_id]));
    }

    /** @return Collection<int, array<string, mixed>> the visitor's questions and answers, oldest first */
    public function conversation(string $visitor): Collection
    {
        return AssistantExchange::with('user:id,name,email')->where('visitor', $visitor)->oldest('id')->limit(500)->get()
            ->map(fn (AssistantExchange $exchange) => ['id' => $exchange->id, 'answer' => $exchange->answer] + $this->summary($exchange));
    }

    /** @return array<string, mixed> */
    private function summary(AssistantExchange $exchange): array
    {
        return [
            'user' => $exchange->user?->only(['id', 'name', 'email']),
            'ip' => $exchange->ip,
            'page' => $exchange->page,
            'question' => $exchange->question,
            'at' => $exchange->created_at?->timezone('Africa/Cairo')->toDateTimeString(),
        ];
    }
}
