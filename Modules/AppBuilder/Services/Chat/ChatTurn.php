<?php

namespace Modules\AppBuilder\Services\Chat;

/** Claude's answer to one customer message, already checked against the expected shape. */
final readonly class ChatTurn
{
    public function __construct(
        public string $reply,
        public bool $needsTeam,
        public bool $quoteReady,
        public string $title,
        public string $spec,
        public int $estimatedTokens,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public bool $complex = false,
        public bool $isFix = false,
        /** @var list<array{name: string, value: string}> access details the customer typed, saved apart and masked */
        public array $secrets = [],
        public float $designFee = 0,
        public string $projectType = 'other',
        public string $starter = '',
        /** @var list<string> free designs shown as pictures under this answer */
        public array $designs = [],
        /** a plain answer while a price is shown: the price and its program stay as they are */
        public bool $keepQuote = false,
        /** the customer asked to put the delivered changes live; the worker releases them */
        public bool $releaseNow = false,
        /** an open request this quote takes the place of (the customer changed or re-classified it), 0 for none */
        public int $replacesRequest = 0,
        /** really another program, not part of this one's main idea: always priced */
        public bool $isNewProject = false,
        /** the customer's message only played with the chat; enough of these close the chat */
        public bool $notSerious = false,
        /** a fact the chat is not sure of, asked of the programmer; '' when it knows */
        public string $askEngineer = '',
        /** a site the customer wants copied, to walk through before the price; '' for none */
        public string $tourSite = '',
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, int $inputTokens = 0, int $outputTokens = 0): self
    {
        $quote = is_array($data['quote'] ?? null) ? $data['quote'] : [];

        return new self(
            reply: trim((string) ($data['reply'] ?? '')),
            needsTeam: (bool) ($data['needs_team'] ?? false),
            quoteReady: (bool) ($quote['ready'] ?? false) && trim((string) ($quote['spec'] ?? '')) !== '',
            title: trim((string) ($quote['title'] ?? '')),
            spec: trim((string) ($quote['spec'] ?? '')),
            estimatedTokens: max(0, (int) ($quote['estimated_output_tokens'] ?? 0)),
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            complex: (bool) ($quote['complex'] ?? false),
            isFix: ($quote['kind'] ?? 'build') === 'fix',
            secrets: array_values(array_filter((array) ($data['secrets'] ?? []), 'is_array')),
            designFee: max(0, (float) ($quote['design_fee'] ?? 0)),
            projectType: (string) ($quote['project_type'] ?? 'other'),
            starter: trim((string) ($data['starter'] ?? '')),
            designs: array_values(array_slice(array_filter((array) ($data['designs'] ?? []), 'is_string'), 0, 6)),
            keepQuote: (bool) ($data['keep_quote'] ?? false),
            releaseNow: (bool) ($data['release_now'] ?? false),
            replacesRequest: max(0, (int) ($data['replaces_request'] ?? 0)),
            isNewProject: ($quote['kind'] ?? 'build') === 'new_project',
            notSerious: (bool) ($data['not_serious'] ?? false),
            askEngineer: trim((string) ($data['ask_engineer'] ?? '')),
            tourSite: trim((string) ($data['tour_site'] ?? '')),
        );
    }

    /** What is stored and shown back to Claude on the next turn. */
    public function payload(): array
    {
        return [
            'reply' => $this->reply,
            'needs_team' => $this->needsTeam,
            'ask_engineer' => $this->askEngineer,
            'quote' => [
                'ready' => $this->quoteReady,
                'title' => $this->title,
                'spec' => $this->spec,
                'estimated_output_tokens' => $this->estimatedTokens,
                'kind' => $this->isFix ? 'fix' : ($this->isNewProject ? 'new_project' : 'build'),
                'complex' => $this->complex,
                'design_fee' => $this->designFee,
                'project_type' => $this->projectType,
            ],
            'keep_quote' => $this->keepQuote,
            'release_now' => $this->releaseNow,
            'not_serious' => $this->notSerious,
            'replaces_request' => $this->replacesRequest,
            'starter' => $this->starter,
            'designs' => $this->designs,
            'tour_site' => $this->tourSite,
        ];
    }
}
