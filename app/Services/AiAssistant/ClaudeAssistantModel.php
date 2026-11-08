<?php

namespace App\Services\AiAssistant;

use Anthropic\Client;

/** Calls the Claude API. Thinking stays adaptive; low effort keeps a chat reply quick and cheap. */
class ClaudeAssistantModel implements AssistantModel
{
    private const MAX_OUTPUT_TOKENS = 1500;

    public function reply(string $system, array $messages, array $tools): array
    {
        $response = (new Client(apiKey: config('services.anthropic.key')))->beta->messages->create(
            betas: ['server-side-fallback-2026-07-01'],
            fallbacks: 'default',
            model: config('services.anthropic.model'),
            maxTokens: self::MAX_OUTPUT_TOKENS,
            system: [['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']]],
            messages: $messages,
            tools: $tools,
            outputConfig: ['effort' => 'low'],
        );

        $text = '';
        $toolCalls = [];
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
            if ($block->type === 'tool_use') {
                $toolCalls[] = ['id' => $block->id, 'name' => $block->name];
            }
        }

        return ['text' => trim($text), 'tool_calls' => $toolCalls, 'content' => $response->content];
    }
}
