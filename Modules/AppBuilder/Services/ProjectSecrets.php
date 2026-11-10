<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Str;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderSecret;

/**
 * Access details (SSH, FTP, API keys) the customer or the team gives for a project.
 * They are stored encrypted, masked out of the chat history, and handed only to the build worker.
 */
class ProjectSecrets
{
    public const MASK = '••••';

    /** Shorter values are not masked in text, so a word like "root" in a sentence stays readable. */
    private const MIN_MASK_LENGTH = 6;

    /** @param list<array{name?: string, value?: string}> $secrets */
    public function save(BuilderProject $project, array $secrets, string $source): void
    {
        foreach ($secrets as $secret) {
            $name = Str::snake(trim((string) ($secret['name'] ?? '')));
            $value = trim((string) ($secret['value'] ?? ''));
            if ($name === '' || $value === '') {
                continue;
            }

            BuilderSecret::updateOrCreate(
                ['project_id' => $project->id, 'name' => mb_substr($name, 0, 60)],
                ['value' => $value, 'source' => $source],
            );
        }
    }

    /** Replaces every saved value in the text, so a secret typed in the chat is not kept in plain text. */
    public function mask(BuilderProject $project, string $text): string
    {
        $values = $this->values($project)->filter(fn (string $value) => mb_strlen($value) >= self::MIN_MASK_LENGTH);

        return $values->reduce(fn (string $carry, string $value) => str_replace($value, self::MASK, $carry), $text);
    }

    /** @return list<array{name: string, value: string}> for the worker only */
    public function forWorker(BuilderProject $project): array
    {
        return $project->secrets()->get()
            ->map(fn (BuilderSecret $secret) => ['name' => $secret->name, 'value' => (string) $secret->value])
            ->values()
            ->all();
    }

    /** @return list<array{id: int, name: string, source: string, updated_at: ?string}> names only, for the admin page */
    public function listing(BuilderProject $project): array
    {
        return $project->secrets()->get()
            ->map(fn (BuilderSecret $secret) => [
                'id' => $secret->id,
                'name' => $secret->name,
                'source' => $secret->source,
                'updated_at' => $secret->updated_at?->toDateTimeString(),
            ])
            ->values()
            ->all();
    }

    public function hasDeployTarget(BuilderProject $project): bool
    {
        return $project->secrets()->whereIn('name', ['ssh_host', 'ftp_host'])->exists();
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    private function values(BuilderProject $project)
    {
        return $project->secrets()->get()->map(fn (BuilderSecret $secret) => (string) $secret->value)->filter();
    }
}
