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
        );
    }

    /** What is stored and shown back to Claude on the next turn. */
    public function payload(): array
    {
        return [
            'reply' => $this->reply,
            'needs_team' => $this->needsTeam,
            'quote' => [
                'ready' => $this->quoteReady,
                'title' => $this->title,
                'spec' => $this->spec,
                'estimated_output_tokens' => $this->estimatedTokens,
                'kind' => $this->isFix ? 'fix' : 'build',
                'complex' => $this->complex,
                'design_fee' => $this->designFee,
                'project_type' => $this->projectType,
            ],
        ];
    }
}
