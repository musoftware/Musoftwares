<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserMergeService;
use Illuminate\Console\Command;
use Throwable;

class MergeUsersCommand extends Command
{
    protected $signature = 'users:merge
                            {survivor : The user ID to keep}
                            {duplicate : The user ID to merge into the survivor}
                            {--dry-run : Preview the merge without writing}
                            {--field=* : Per-field resolution as name=value (value|survivor|duplicate)}
                            {--yes : Skip interactive confirmation}
                            {--admin= : Override admin actor id for audit (defaults to 0 = system)}';

    protected $description = 'Merge a duplicate user (and its child rows) into a survivor user account.';

    public function handle(UserMergeService $service): int
    {
        $survivorId  = (int) $this->argument('survivor');
        $duplicateId = (int) $this->argument('duplicate');
        $adminId     = (int) ($this->option('admin') ?? 0);
        $dryRun      = (bool) $this->option('dry-run');

        try {
            $preview = $service->preview($survivorId, $duplicateId);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Survivor  #{$preview['survivor']['id']}  {$preview['survivor']['name']} <{$preview['survivor']['email']}>");
        $this->info("Duplicate #{$preview['duplicate']['id']} {$preview['duplicate']['name']} <{$preview['duplicate']['email']}>");

        $this->line('');
        $this->info('Conflicting fields:');
        foreach ($preview['field_conflicts'] as $field => $vals) {
            $this->line(sprintf(
                '  %-18s survivor=%s  duplicate=%s',
                $field,
                var_export($vals['survivor'], true),
                var_export($vals['duplicate'], true)
            ));
        }

        $this->line('');
        $this->info('Child rows to be reassigned (count by table.column):');
        foreach ($preview['child_counts'] as $key => $count) {
            $this->line("  {$key}: {$count}");
        }

        if ($dryRun) {
            $this->warn('Dry-run mode: no writes performed.');
            return self::SUCCESS;
        }

        $resolutions = $this->collectResolutions($preview['field_conflicts']);

        if (!$this->option('yes')) {
            if (!$this->confirm('Proceed with merging duplicate into survivor?')) {
                $this->warn('Aborted.');
                return self::SUCCESS;
            }
        }

        try {
            $service->merge($survivorId, $duplicateId, $resolutions, $adminId);
        } catch (Throwable $e) {
            $this->error('Merge failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Merge completed. Duplicate #{$duplicateId} soft-deleted and reassigned to survivor #{$survivorId}.");
        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{survivor: mixed, duplicate: mixed}>  $conflicts
     * @return array<string, string>
     */
    private function collectResolutions(array $conflicts): array
    {
        $resolutions = [];

        foreach ($this->option('field') ?? [] as $entry) {
            if (!str_contains($entry, '=')) {
                $this->warn("Skipping invalid --field entry: {$entry}");
                continue;
            }
            [$field, $value] = explode('=', $entry, 2);
            $resolutions[trim($field)] = trim($value);
        }

        if ($resolutions === [] && $conflicts !== [] && $this->option('yes')) {
            foreach ($conflicts as $field => $_) {
                $resolutions[$field] = 'survivor';
            }
        }

        if ($resolutions === [] && $conflicts !== [] && !$this->option('yes')) {
            foreach ($conflicts as $field => $vals) {
                $choice = $this->choice(
                    "Conflict on '{$field}' — survivor={$vals['survivor']} duplicate={$vals['duplicate']}",
                    ['survivor', 'duplicate', (string) ($vals['survivor'] ?? '')],
                    'survivor'
                );
                $resolutions[$field] = $choice;
            }
        }

        return $resolutions;
    }
}
