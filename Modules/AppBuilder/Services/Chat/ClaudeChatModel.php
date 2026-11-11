<?php

namespace Modules\AppBuilder\Services\Chat;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;

/** Talks with the customer through Claude and always gets back the same JSON shape. */
class ClaudeChatModel implements ChatModel
{
    private const MAX_OUTPUT_TOKENS = 16000;

    public function reply(string $system, array $turns): ChatTurn
    {
        if (! config('builder.chat_web_search.enabled')) {
            return $this->turn($this->ask($system, $turns, false));
        }

        // the web is a help, never a risk: a search that pauses, fails or breaks the answer means one plain ask instead
        try {
            return $this->turn($this->ask($system, $turns, true));
        } catch (ChatUnavailable $exception) {
            \Illuminate\Support\Facades\Log::warning('App builder: chat with web search failed, answering without it', ['error' => $exception->getMessage()]);

            return $this->turn($this->ask($system, $turns, false));
        }
    }

    private function turn($message): ChatTurn
    {
        if ($message->stopReason !== 'end_turn') {
            throw new ChatUnavailable('Claude stopped early: '.$message->stopReason);
        }

        return ChatTurn::fromArray($this->json($message->content), (int) $message->usage->inputTokens, (int) $message->usage->outputTokens);
    }

    /** One call to Claude; with the web on, it may search before it answers (for things the team has not built before). */
    private function ask(string $system, array $turns, bool $web)
    {
        try {
            return (new Client(apiKey: config('services.anthropic.key')))->beta->messages->create(
                ...$this->fallbackOptions(),
                ...($web ? ['tools' => [$this->webSearch()]] : []),
                model: config('builder.chat_model'),
                maxTokens: self::MAX_OUTPUT_TOKENS,
                system: [['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']]],
                messages: $turns,
                outputConfig: ['effort' => config('builder.chat_effort'), 'format' => ['type' => 'json_schema', 'schema' => $this->schema()]],
            );
        } catch (APIException $exception) {
            throw new ChatUnavailable($exception->getMessage(), previous: $exception);
        }
    }

    private function webSearch(): array
    {
        return ['type' => config('builder.chat_web_search.tool'), 'name' => 'web_search', 'maxUses' => (int) config('builder.chat_web_search.max_uses')];
    }

    /** Refusal fallback only works on Opus and Sonnet models, so it is off for the cheap Haiku chat. */
    private function fallbackOptions(): array
    {
        return config('builder.chat_fallback')
            ? ['betas' => ['server-side-fallback-2026-07-01'], 'fallbacks' => 'default']
            : [];
    }

    /** The answer is the text after the last search; search citations can split it into several text blocks. */
    private function json(iterable $content): array
    {
        $answer = '';
        foreach ($content as $block) {
            $answer = $block->type === 'text' ? $answer.$block->text : '';
        }

        $data = json_decode(trim($answer), true);
        if (is_array($data) && isset($data['reply'], $data['quote'])) {
            return $data;
        }

        throw new ChatUnavailable('Claude gave no usable JSON.');
    }

    private function schema(): array
    {
        $quote = [
            'type' => 'object',
            'properties' => [
                'ready' => ['type' => 'boolean'],
                'title' => ['type' => 'string'],
                'spec' => ['type' => 'string'],
                'estimated_output_tokens' => ['type' => 'integer'],
                'kind' => ['type' => 'string', 'enum' => ['build', 'fix']],
                'complex' => ['type' => 'boolean'],
                'design_fee' => ['type' => 'number'],
                'project_type' => ['type' => 'string', 'enum' => array_keys((array) config('builder.project_types'))],
            ],
            'required' => ['ready', 'title', 'spec', 'estimated_output_tokens', 'kind', 'complex', 'design_fee', 'project_type'],
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'reply' => ['type' => 'string'],
                'needs_team' => ['type' => 'boolean'],
                'quote' => $quote,
                'secrets' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['name' => ['type' => 'string'], 'value' => ['type' => 'string']],
                    'required' => ['name', 'value'],
                    'additionalProperties' => false,
                ]],
            ],
            'required' => ['reply', 'needs_team', 'quote', 'secrets'],
            'additionalProperties' => false,
        ];
    }
}
