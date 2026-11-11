<?php

namespace Modules\AppBuilder\Services\Starters;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use RuntimeException;

/** The chat model writes the card as strict JSON. */
class ClaudeStarterCardWriter implements StarterCardWriter
{
    private const MAX_OUTPUT_TOKENS = 1500;

    private const SYSTEM = <<<'TEXT'
        Musoftware built the program described below for one customer. When another customer asks for the same kind of
        program, the team will start from it. Write its short internal card, so the sales chat can recognise a match.

        - Describe what the program does in general terms, for the kind of business it serves ("a booking system for
          clinics", not the clinic's name).
        - Never include anything that identifies the first customer: no business, brand, product or person names, no
          phones, emails, addresses, cities, links, prices or numbers from their business.
        - title: at most 6 words. summary: one plain sentence about what it does. features: 5 to 10 short items.
        - Plain English.
        TEXT;

    public function write(string $brief): array
    {
        try {
            $message = (new Client(apiKey: config('services.anthropic.key')))->beta->messages->create(
                model: config('builder.chat_model'),
                maxTokens: self::MAX_OUTPUT_TOKENS,
                system: [['type' => 'text', 'text' => self::SYSTEM]],
                messages: [['role' => 'user', 'content' => $brief]],
                outputConfig: ['effort' => config('builder.chat_effort'), 'format' => ['type' => 'json_schema', 'schema' => self::schema()]],
            );
        } catch (APIException $exception) {
            throw new RuntimeException('Card not written: '.$exception->getMessage(), previous: $exception);
        }

        $text = '';
        foreach ($message->content as $block) {
            $text .= $block->type === 'text' ? $block->text : '';
        }
        $card = json_decode($text, true);
        if (! is_array($card) || ! isset($card['title'], $card['summary'], $card['features'])) {
            throw new RuntimeException('Card not written: the answer was not a card.');
        }

        return $card;
    }

    private static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'features' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['title', 'summary', 'features'],
            'additionalProperties' => false,
        ];
    }
}
