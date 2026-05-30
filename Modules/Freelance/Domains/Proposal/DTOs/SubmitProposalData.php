<?php

namespace Modules\Freelance\Domains\Proposal\DTOs;

readonly class SubmitProposalData
{
    public function __construct(
        public int $jobId,
        public int $freelancerId,
        public string $coverLetter,
        public int $proposedBudgetPoints,
        public int $pointsSpent,
    ) {}
}
