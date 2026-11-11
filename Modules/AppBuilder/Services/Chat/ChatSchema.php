<?php

namespace Modules\AppBuilder\Services\Chat;

/** The one JSON shape every chat model answers with: the reply, the quote and any access details the customer typed. */
final class ChatSchema
{
    public static function reply(): array
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
                // a program Musoftware already has that this idea starts from, '' for none
                'starter' => ['type' => 'string', 'enum' => ['', ...app(\Modules\AppBuilder\Services\Starters::class)->slugs()]],
                'secrets' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['name' => ['type' => 'string'], 'value' => ['type' => 'string']],
                    'required' => ['name', 'value'],
                    'additionalProperties' => false,
                ]],
            ],
            'required' => ['reply', 'needs_team', 'quote', 'starter', 'secrets'],
            'additionalProperties' => false,
        ];
    }

    /** The decoded answer when it has the parts every turn needs, otherwise null. */
    public static function decode(string $text): ?array
    {
        $data = json_decode(trim($text), true);

        return is_array($data) && isset($data['reply'], $data['quote']) ? $data : null;
    }
}
