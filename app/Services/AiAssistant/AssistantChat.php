<?php

namespace App\Services\AiAssistant;

use App\Models\User;
use App\Support\SiteNav;
use Illuminate\Support\Facades\Session;

/** One conversation with the assistant. The history lives on the server so a visitor cannot forge it. */
class AssistantChat
{
    private const SESSION_KEY = 'ai_assistant.history';

    private const MAX_HISTORY_MESSAGES = 12;

    private const MAX_TOOL_ROUNDS = 2;

    public function __construct(
        private readonly AssistantModel $model,
        private readonly AssistantPrompt $prompt,
        private readonly AssistantTools $tools,
    ) {}

    /** @return list<array{role: string, content: string}> */
    public function history(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function reset(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function answer(string $question, ?User $user): string
    {
        $history = $this->history();
        [$lastQuestion, $lastAnswer] = array_slice($history, -2) + [null, null];
        if (($lastQuestion['content'] ?? null) === $question) {
            return $lastAnswer['content']; // the same message again costs nothing
        }

        $asked = [...$history, ['role' => 'user', 'content' => $question]];
        $answer = $this->runTurn($asked, $user);

        Session::put(self::SESSION_KEY, array_slice(
            [...$asked, ['role' => 'assistant', 'content' => $answer]],
            -self::MAX_HISTORY_MESSAGES,
        ));

        return $answer;
    }

    public static function fallbackMessage(): string
    {
        return self::say('معرفتش أرد دلوقتي. تقدر تكلمنا على واتساب:', 'Sorry, I could not answer that now. You can message us on WhatsApp:');
    }

    public static function limitMessage(): string
    {
        return self::say('بعت رسايل كتير. جرب كمان شوية، أو كلمنا على واتساب:', 'You have sent many messages. Please try later or message us on WhatsApp:');
    }

    private static function say(string $arabic, string $english): string
    {
        return (app()->getLocale() === 'ar' ? $arabic : $english)."\n".SiteNav::WHATSAPP_URL;
    }

    /** @param list<array<string, mixed>> $messages */
    private function runTurn(array $messages, ?User $user): string
    {
        $system = $this->prompt->build($user);
        $tools = $this->tools->definitions($user);

        for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
            $reply = $this->model->reply($system, $messages, $tools);

            if ($reply['tool_calls'] === []) {
                return $reply['text'] !== '' ? $reply['text'] : self::fallbackMessage();
            }

            $messages[] = ['role' => 'assistant', 'content' => $reply['content']];
            $messages[] = ['role' => 'user', 'content' => $this->toolResults($reply['tool_calls'], $user)];
        }

        return self::fallbackMessage();
    }

    /**
     * @param  list<array{id: string, name: string}>  $calls
     * @return list<array<string, string>>
     */
    private function toolResults(array $calls, ?User $user): array
    {
        return array_map(fn (array $call) => [
            'type' => 'tool_result',
            'toolUseID' => $call['id'],
            'content' => $this->tools->run($call['name'], $user),
        ], $calls);
    }
}
