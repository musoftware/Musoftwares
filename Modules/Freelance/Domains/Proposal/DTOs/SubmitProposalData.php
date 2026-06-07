<?php

namespace Modules\Freelance\Domains\Proposal\DTOs;

readonly class SubmitProposalData
{
    public function __construct(
        public int $jobId,
        public int $freelancerId,
        public string $coverLetter,
        public float $bidAmount,
        public int $currencyId,
        public int $pointsSpent,
    ) {}
}
