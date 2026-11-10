<?php

namespace Modules\Shop\Services\Content;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;

/** One Claude call that must answer with JSON of a known shape, over the platform's existing connection. */
class ClaudeJsonRequest
{
    private const MAX_OUTPUT_TOKENS = 6000;

    /**
     * @param  string|list<array<string, mixed>>  $content
     * @param  array<string, array<string, mixed>>  $properties
     * @return array<string, mixed>
     *
     * @throws ProductCopyUnavailable
     */
    public function ask(string $system, string|array $content, array $properties): array
    {
        try {
            $message = (new Client(apiKey: config('services.anthropic.key')))->beta->messages->create(
                betas: ['server-side-fallback-2026-07-01'],
                fallbacks: 'default',
                model: config('services.anthropic.model'),
                maxTokens: self::MAX_OUTPUT_TOKENS,
                system: [['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']]],
                messages: [['role' => 'user', 'content' => $content]],
                outputConfig: ['effort' => 'medium', 'format' => $this->format($properties)],
            );
        } catch (APIException $exception) {
            throw new ProductCopyUnavailable($exception->getMessage(), previous: $exception);
        }

        return $this->decode($message->stopReason, $message->content, array_keys($properties));
    }

    private function format(array $properties): array
    {
        return [
            'type' => 'json_schema',
            'schema' => ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties), 'additionalProperties' => false],
        ];
    }

    private function decode(?string $stopReason, iterable $content, array $fields): array
    {
        if ($stopReason !== 'end_turn') {
            throw new ProductCopyUnavailable('Claude stopped early: '.$stopReason);
        }

        foreach ($content as $block) {
            $data = $block->type === 'text' ? json_decode($block->text, true) : null;

            if (is_array($data) && array_diff($fields, array_keys($data)) === []) {
                return $data;
            }
        }

        throw new ProductCopyUnavailable('Claude gave no usable JSON.');
    }
}
