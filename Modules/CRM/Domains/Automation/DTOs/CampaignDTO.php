<?php

namespace Modules\CRM\Domains\Automation\DTOs;

class CampaignDTO
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly ?int $branchId,
        public readonly int $userId,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $targetAudience,
        public readonly ?array $settings
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            workspaceId: $data['workspace_id'],
            branchId: $data['branch_id'] ?? null,
            userId: $data['user_id'],
            name: $data['name'],
            type: $data['type'] ?? 'email',
            status: $data['status'] ?? 'draft',
            targetAudience: $data['target_audience'] ?? null,
            settings: $data['settings'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'workspace_id'    => $this->workspaceId,
            'branch_id'       => $this->branchId,
            'user_id'         => $this->userId,
            'name'            => $this->name,
            'type'            => $this->type,
            'status'          => $this->status,
            'target_audience' => $this->targetAudience,
            'settings'        => $this->settings,
        ];
    }
}
