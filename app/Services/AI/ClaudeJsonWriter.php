<?php

namespace App\Services\AI;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use Illuminate\Support\Facades\Log;

/** Sends one prompt to Claude and gets back JSON in the given shape, or null when Claude fails or stops early. */
class ClaudeJsonWriter
{
    private const MAX_OUTPUT_TOKENS = 16000;

    public static function available(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /** @param  array<string, mixed>  $schema  a JSON schema for the answer */
    public function write(string $prompt, array $schema): ?array
    {
        try {
            $message = (new Client(apiKey: config('services.anthropic.key')))->beta->messages->create(
                model: config('services.anthropic.blog_model'),
                maxTokens: self::MAX_OUTPUT_TOKENS,
                messages: [['role' => 'user', 'content' => $prompt]],
                outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
            );
        } catch (APIException $exception) {
            Log::warning('Claude JSON writer failed.', ['error' => $exception->getMessage()]);

            return null;
        }

        if ($message->stopReason !== 'end_turn') {
            Log::warning('Claude JSON writer stopped early.', ['stop_reason' => $message->stopReason]);

            return null;
        }

        return $this->json($message->content);
    }

    private function json(iterable $content): ?array
    {
        foreach ($content as $block) {
            $data = $block->type === 'text' ? json_decode($block->text, true) : null;
            if (is_array($data)) {
                return $data;
            }
        }

        return null;
    }
}
