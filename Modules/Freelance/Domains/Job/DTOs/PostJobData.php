<?php

namespace Modules\Freelance\Domains\Job\DTOs;

readonly class PostJobData
{
    public function __construct(
        public int $clientId,
        public string $title,
        public string $description,
        public float $budget,
        public int $currencyId,
        public string $type,
        public ?string $duration,
        public array $skills,
    ) {}
}
