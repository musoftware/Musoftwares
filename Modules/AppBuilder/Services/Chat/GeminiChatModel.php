<?php

namespace Modules\AppBuilder\Services\Chat;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Google's free Gemini tier, for the first messages of a guest who has not seen a price yet: most of that talk never
 * becomes a project, so it should cost nothing. It answers in the same JSON shape as Claude; anything wrong (the free
 * limit, a bad answer, a timeout) is a ChatUnavailable, and the chat then asks Claude instead.
 */
class GeminiChatModel implements ChatModel
{
    private const TIMEOUT_SECONDS = 60;

    public function reply(string $system, array $turns): ChatTurn
    {
        try {
            $response = Http::withHeaders(['x-goog-api-key' => (string) config('builder.free_chat.key')])
                ->timeout(self::TIMEOUT_SECONDS)
                ->post(config('builder.free_chat.url').'/'.config('builder.free_chat.model').':generateContent', [
                    'system_instruction' => ['parts' => [['text' => $system]]],
                    'contents' => array_map(fn (array $turn) => $this->content($turn), $turns),
                    'generationConfig' => ['responseMimeType' => 'application/json', 'responseJsonSchema' => ChatSchema::reply()],
                ]);
        } catch (ConnectionException $exception) {
            throw new ChatUnavailable('Gemini not reachable: '.$exception->getMessage(), previous: $exception);
        }

        if (! $response->successful()) {
            throw new ChatUnavailable('Gemini refused: '.$response->status().' '.mb_substr($response->body(), 0, 300));
        }

        $text = collect($response->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode('');
        $data = ChatSchema::decode($text) ?? throw new ChatUnavailable('Gemini gave no usable JSON.');

        // the free tier costs nothing, so its tokens are not counted against the chat's cost
        return ChatTurn::fromArray($data);
    }

    /** One of our turns (text, or Claude-style blocks with pictures and PDFs) as a Gemini content. */
    private function content(array $turn): array
    {
        $blocks = is_string($turn['content']) ? [['type' => 'text', 'text' => $turn['content']]] : (array) $turn['content'];

        return [
            'role' => $turn['role'] === 'assistant' ? 'model' : 'user',
            'parts' => array_values(array_filter(array_map(fn ($block) => $this->part((array) $block), $blocks))) ?: [['text' => '.']],
        ];
    }

    private function part(array $block): ?array
    {
        $source = (array) ($block['source'] ?? []);

        return match ($block['type'] ?? '') {
            'text' => ['text' => (string) $block['text']],
            'image', 'document' => ($source['type'] ?? '') === 'base64'
                ? ['inline_data' => ['mime_type' => $source['media_type'], 'data' => $source['data']]]
                : null,
            default => null,
        };
    }
}
