<?php

namespace App\Console\Commands;

use App\Services\UserMergeService;
use Illuminate\Console\Command;
use Throwable;

class MergeUsersCommand extends Command
{
    protected $signature = 'users:merge
                            {survivor : The user ID to keep}
                            {duplicates* : One or more duplicate user IDs to merge into the survivor (comma-separated also accepted)}
                            {--dry-run : Preview the merge without writing}
                            {--field=* : Per-field resolution as name=value (value|survivor|duplicate)}
                            {--yes : Skip interactive confirmation}
                            {--admin= : Override admin actor id for audit (defaults to 0 = system)}';

    protected $description = 'Merge one or more duplicate users (and their child rows) into a survivor user account.';

    public function handle(UserMergeService $service): int
    {
        $survivorId = (int) $this->argument('survivor');
        $adminId = (int) ($this->option('admin') ?? 0);
        $dryRun = (bool) $this->option('dry-run');

        $duplicates = $this->argument('duplicates');
        $duplicates = is_array($duplicates) ? $duplicates : [$duplicates];
        $duplicateIds = [];
        foreach ($duplicates as $entry) {
            foreach (explode(',', (string) $entry) as $piece) {
                $piece = trim($piece);
                if ($piece !== '' && ctype_digit($piece)) {
                    $duplicateIds[] = (int) $piece;
                }
            }
        }
        $duplicateIds = array_values(array_unique($duplicateIds));

        if ($duplicateIds === []) {
            $this->error('At least one duplicate user id is required.');

            return self::FAILURE;
        }

        $allConflicts = [];
        $allCounts = [];
        foreach ($duplicateIds as $duplicateId) {
            try {
                $preview = $service->preview($survivorId, $duplicateId);
            } catch (Throwable $e) {
                $this->error("Preview for duplicate #{$duplicateId} failed: {$e->getMessage()}");

                return self::FAILURE;
            }

            $this->info("Survivor   #{$preview['survivor']['id']}  {$preview['survivor']['name']} <{$preview['survivor']['email']}>");
            $this->info("Duplicate  #{$preview['duplicate']['id']} {$preview['duplicate']['name']} <{$preview['duplicate']['email']}>");

            $this->line('  Conflicting fields:');
            foreach ($preview['field_conflicts'] as $field => $vals) {
                $this->line(sprintf(
                    '    %-18s survivor=%s  duplicate=%s',
                    $field,
                    var_export($vals['survivor'], true),
                    var_export($vals['duplicate'], true)
                ));
            }
            foreach ($preview['field_conflicts'] as $field => $vals) {
                $allConflicts[$field] = $vals;
            }

            $this->line('  Child rows to be reassigned:');
            foreach ($preview['child_counts'] as $key => $count) {
                $this->line("    {$key}: {$count}");
                $allCounts[$key] = ($allCounts[$key] ?? 0) + $count;
            }
            $this->line('');
        }

        if ($dryRun) {
            $this->warn('Dry-run mode: no writes performed.');

            return self::SUCCESS;
        }

        $resolutions = $this->collectResolutions($allConflicts);

        if (! $this->option('yes')) {
            if (! $this->confirm('Proceed with merging duplicates into survivor?')) {
                $this->warn('Aborted.');

                return self::SUCCESS;
            }
        }

        try {
            $outcomes = $service->mergeMany($survivorId, $duplicateIds, $resolutions, $adminId);
        } catch (Throwable $e) {
            $this->error('Merge failed: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($outcomes as $o) {
            $aliasNote = $o['alias_added'] ? ' (email preserved as alias)' : '';
            $this->info("Duplicate #{$o['duplicate_id']}: {$o['status']}{$aliasNote}");
        }

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
            if (! str_contains($entry, '=')) {
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

        if ($resolutions === [] && $conflicts !== [] && ! $this->option('yes')) {
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
