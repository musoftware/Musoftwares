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
        public int $minProposalPoints,
        public string $type,
        public ?string $serviceType,
        public ?string $country,
        public ?string $city,
        public ?string $district,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $duration,
        public array $skills,
    ) {}
}
