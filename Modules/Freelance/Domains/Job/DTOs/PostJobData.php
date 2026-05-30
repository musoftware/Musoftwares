<?php

namespace Modules\Freelance\Domains\Job\DTOs;

readonly class PostJobData
{
    public function __construct(
        public int $clientId,
        public string $title,
        public string $description,
        public int $budgetPoints,
        public int $minProposalPoints,
        public string $type,
        public ?string $duration,
        public array $skills,
    ) {}
}
