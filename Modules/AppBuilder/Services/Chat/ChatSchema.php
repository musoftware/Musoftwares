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
                'kind' => ['type' => 'string', 'enum' => ['build', 'fix', 'new_project']],
                'complex' => ['type' => 'boolean'],
                'design_fee' => ['type' => 'number'],
                'project_type' => ['type' => 'string', 'enum' => array_keys((array) config('builder.project_types'))],
                // the estimator's view of the same work: the customer's pages or screens, and the price-list modules
                'pages' => ['type' => 'integer'],
                'modules' => self::modules(),
            ],
            'required' => ['ready', 'title', 'spec', 'estimated_output_tokens', 'kind', 'complex', 'design_fee', 'project_type', 'pages', 'modules'],
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'reply' => ['type' => 'string'],
                'needs_team' => ['type' => 'boolean'],
                // a fact this answer depends on that the chat is not sure of, sent to the programmer; '' for none
                'ask_engineer' => ['type' => 'string'],
                'quote' => $quote,
                // true when a price is already shown and this answer changes nothing in what gets built
                'keep_quote' => ['type' => 'boolean'],
                // the customer asks to put the delivered changes live (upload, publish, update the app): the worker releases them
                'release_now' => ['type' => 'boolean'],
                // the message only plays with the chat instead of asking for a program; see "A visitor who is not serious"
                'not_serious' => ['type' => 'boolean'],
                // the number of an open request in the list that this quote takes the place of, 0 for none
                'replaces_request' => ['type' => 'integer'],
                // a program Musoftware already has that this idea starts from, '' for none
                'starter' => ['type' => 'string', 'enum' => ['', ...app(\Modules\AppBuilder\Services\Starters::class)->slugs()]],
                // free ready designs to show as pictures under this answer, so the customer picks one
                'designs' => self::designs(),
                // a site the customer wants copied: the worker walks through it and takes pictures; '' for none
                'tour_site' => ['type' => 'string'],
                'secrets' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'properties' => ['name' => ['type' => 'string'], 'value' => ['type' => 'string']],
                    'required' => ['name', 'value'],
                    'additionalProperties' => false,
                ]],
            ],
            'required' => ['reply', 'needs_team', 'ask_engineer', 'quote', 'keep_quote', 'release_now', 'not_serious', 'replaces_request', 'starter', 'designs', 'tour_site', 'secrets'],
            'additionalProperties' => false,
        ];
    }

    private static function modules(): array
    {
        $keys = app(\Modules\AppBuilder\Services\EstimatorPrice::class)->moduleKeys();

        return ['type' => 'array', 'items' => $keys === [] ? ['type' => 'string'] : ['type' => 'string', 'enum' => $keys]];
    }

    private static function designs(): array
    {
        $slugs = app(\Modules\AppBuilder\Services\Starters::class)->designSlugs();

        return ['type' => 'array', 'items' => $slugs === [] ? ['type' => 'string'] : ['type' => 'string', 'enum' => $slugs]];
    }

    /** The decoded answer when it has the parts every turn needs, otherwise null. */
    public static function decode(string $text): ?array
    {
        $data = json_decode(trim($text), true);

        return is_array($data) && isset($data['reply'], $data['quote']) ? $data : null;
    }
}
