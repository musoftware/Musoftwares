<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\UserEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class UserMergeService
{
    /** Scalar user columns eligible for per-field resolution. */
    public const RESOLVABLE_FIELDS = [
        'name',
        'email',
        'phone',
        'country',
        'city',
        'mobile_1',
        'mobile_2',
        'telegram_username',
        'whatsapp_number',
    ];

    /** FK columns we WILL reassign (child user_id / client_id). */
    private const REASSIGN_COLUMNS = ['user_id', 'client_id'];

    /** FK columns we explicitly will NOT touch (semantic/audit). */
    private const PROTECTED_COLUMNS = [
        'created_by',
        'updated_by',
        'kyc_verified_by',
        'cost_payable_user_id',
        'credit_user_id',
        'peer_id',
    ];

    /**
     * Elect the oldest (smallest) active user ID among the candidate survivor
     * and duplicates so merges always preserve the older user ID.
     *
     * @param  array<int, int>  $duplicateIds
     * @return array{0: int, 1: array<int, int>}
     */
    public function resolveOldestSurvivor(int $survivorId, array $duplicateIds): array
    {
        $cleanDuplicates = array_values(array_unique(array_filter(
            array_map('intval', $duplicateIds),
            static fn (int $id): bool => $id > 0 && $id !== $survivorId
        )));

        if ($cleanDuplicates === []) {
            return [$survivorId, $cleanDuplicates];
        }

        $activeIds = User::query()
            ->whereIn('id', array_merge([$survivorId], $cleanDuplicates))
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if (! in_array($survivorId, $activeIds, true)) {
            return [$survivorId, $cleanDuplicates];
        }

        $oldestId = min($activeIds);
        if ($oldestId === $survivorId) {
            return [$survivorId, $cleanDuplicates];
        }

        $remaining = array_values(array_filter(
            array_unique(array_merge([$survivorId], $cleanDuplicates)),
            static fn (int $id): bool => $id !== $oldestId
        ));
        sort($remaining);

        return [$oldestId, $remaining];
    }

    public function preview(int $survivorId, int $duplicateId): array
    {
        [$survivor, $duplicate] = $this->loadPair($survivorId, $duplicateId);

        $fieldConflicts = [];
        foreach (self::RESOLVABLE_FIELDS as $field) {
            $s = $survivor->{$field};
            $d = $duplicate->{$field};
            if ($s !== $d && ! ($s === null && $d === '')) {
                $fieldConflicts[$field] = [
                    'survivor' => $s,
                    'duplicate' => $d,
                ];
            }
        }

        $childCounts = $this->collectChildCounts((int) $duplicate->id);

        return [
            'survivor' => $survivor->only(['id', 'name', 'email']),
            'duplicate' => $duplicate->only(['id', 'name', 'email']),
            'field_conflicts' => $fieldConflicts,
            'child_counts' => $childCounts,
        ];
    }

    public function merge(int $survivorId, int $duplicateId, array $resolutions, int $adminId): void
    {
        $this->mergeMany($survivorId, [$duplicateId], $resolutions, $adminId);
    }

    /**
     * Merge one or more duplicate accounts into a survivor.
     *
     * Wraps the entire batch in a single DB transaction. Always elects the
     * oldest (smallest) active user ID among the involved accounts as the
     * surviving primary account.
     *
     * @param  array<int, int>  $duplicateIds
     * @return array<int, array{survivor_id:int, duplicate_id:int, status:string, alias_added:bool, error?:string}>
     */
    public function mergeMany(int $survivorId, array $duplicateIds, array $resolutions, int $adminId): array
    {
        $duplicateIds = $this->validateDuplicateIds($survivorId, $duplicateIds);
        $outcomes = [];

        DB::transaction(function () use ($survivorId, $duplicateIds, $resolutions, $adminId, &$outcomes) {
            [$survivor, $effectiveDuplicateIds, $lockedUsers] = $this->lockAndElectOldest($survivorId, $duplicateIds);
            $effectiveSurvivorId = (int) $survivor->id;

            $batchStats = [
                'per_duplicate' => [],
                'reassignments' => [],
                'tokens_revoked' => 0,
                'roles_deduped' => 0,
                'collisions' => [],
            ];

            foreach ($effectiveDuplicateIds as $duplicateId) {
                $outcomes[] = $this->mergeLockedDuplicate(
                    $survivor,
                    $lockedUsers->get($duplicateId),
                    $duplicateId,
                    $resolutions,
                    $adminId,
                    $batchStats
                );
            }

            $this->recordMergeAuditLog(
                $adminId,
                $effectiveSurvivorId,
                $survivorId,
                $effectiveDuplicateIds,
                $outcomes,
                $batchStats
            );
        });

        return $outcomes;
    }

    /**
     * @param  array<int, mixed>  $duplicateIds
     * @return array<int, int>
     */
    private function validateDuplicateIds(int $survivorId, array $duplicateIds): array
    {
        $cleaned = array_values(array_unique(array_filter(
            array_map('intval', $duplicateIds),
            static fn (int $v): bool => $v > 0
        )));

        if ($cleaned === []) {
            throw new RuntimeException('At least one duplicate user id is required.');
        }

        if (in_array($survivorId, $cleaned, true)) {
            throw new RuntimeException("Duplicate id {$survivorId} cannot equal survivor id {$survivorId}.");
        }

        return $cleaned;
    }

    /**
     * @param  array<int, int>  $duplicateIds
     * @return array{0: User, 1: array<int, int>, 2: \Illuminate\Support\Collection<int, User>}
     */
    private function lockAndElectOldest(int $survivorId, array $duplicateIds): array
    {
        $allIds = array_values(array_unique(array_merge([$survivorId], $duplicateIds)));
        sort($allIds);

        $lockedUsers = User::withTrashed()
            ->whereIn('id', $allIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(static fn (User $u): int => (int) $u->id);

        $initialSurvivor = $lockedUsers->get($survivorId);
        if (! $initialSurvivor || $initialSurvivor->trashed()) {
            throw new RuntimeException("Survivor user #{$survivorId} not found or already soft-deleted.");
        }

        /** @var User $oldestActive */
        $oldestActive = $lockedUsers
            ->filter(static fn (User $u): bool => ! $u->trashed())
            ->sortBy('id')
            ->first();

        $effectiveSurvivorId = (int) $oldestActive->id;
        if ($effectiveSurvivorId === $survivorId) {
            return [$oldestActive, $duplicateIds, $lockedUsers];
        }

        $effectiveDuplicateIds = array_values(array_filter(
            $allIds,
            static fn (int $id): bool => $id !== $effectiveSurvivorId
        ));

        return [$oldestActive, $effectiveDuplicateIds, $lockedUsers];
    }

    /**
     * @param  array<string, mixed>  $batchStats
     * @return array{survivor_id:int, duplicate_id:int, status:string, alias_added:bool, error?:string}
     */
    private function mergeLockedDuplicate(
        User $survivor,
        ?User $duplicate,
        int $duplicateId,
        array $resolutions,
        int $adminId,
        array &$batchStats
    ): array {
        $survivorId = (int) $survivor->id;

        if (! $duplicate || $duplicate->trashed()) {
            return [
                'survivor_id' => $survivorId,
                'duplicate_id' => $duplicateId,
                'status' => 'skipped',
                'alias_added' => false,
                'error' => 'Duplicate not found or already merged.',
            ];
        }

        $collisions = [];
        $snapshot = [
            'survivor_before' => $survivor->only(self::RESOLVABLE_FIELDS),
            'duplicate_before' => $duplicate->only(self::RESOLVABLE_FIELDS),
            'resolutions' => $resolutions,
            'reassignments' => [],
            'tokens_revoked' => 0,
            'roles_deduped' => 0,
            'collisions' => [],
        ];

        $this->applyFieldResolutions($survivor, $duplicate, $resolutions);

        $snapshot['reassignments'] = $this->reassignChildRows($duplicateId, $survivorId, $collisions);
        $snapshot['collisions'] = $collisions;
        $snapshot['tokens_revoked'] = $this->revokeDuplicateTokens($duplicateId);
        $snapshot['roles_deduped'] = $this->mergeRolesAndPermissions($duplicateId, $survivorId);

        $duplicate->forceFill([
            'merged_into_user_id' => $survivorId,
            'deleted_at' => now(),
        ])->save();

        $aliasAdded = $this->promoteDuplicateEmailToAlias($duplicate, $survivor, $adminId);

        $batchStats['per_duplicate'][] = [
            'duplicate_id' => $duplicateId,
            'snapshot' => $snapshot,
            'alias_added' => $aliasAdded,
        ];
        $batchStats['reassignments'] = array_merge($batchStats['reassignments'], $snapshot['reassignments']);
        $batchStats['tokens_revoked'] += $snapshot['tokens_revoked'];
        $batchStats['roles_deduped'] += $snapshot['roles_deduped'];
        $batchStats['collisions'] = array_merge($batchStats['collisions'], $collisions);

        return [
            'survivor_id' => $survivorId,
            'duplicate_id' => $duplicateId,
            'status' => 'merged',
            'alias_added' => $aliasAdded,
        ];
    }

    /**
     * @param  array<int, int>  $duplicateIds
     * @param  array<int, array<string, mixed>>  $outcomes
     * @param  array<string, mixed>  $batchStats
     */
    private function recordMergeAuditLog(
        int $adminId,
        int $survivorId,
        int $requestedSurvivorId,
        array $duplicateIds,
        array $outcomes,
        array $batchStats
    ): void {
        AdminAuditLog::create([
            'actor_user_id' => $adminId,
            'action' => 'users.merged',
            'severity' => AdminAuditLog::SEVERITY_WARNING,
            'target_type' => User::class,
            'target_id' => $survivorId,
            'meta' => [
                'survivor_id' => $survivorId,
                'requested_survivor_id' => $requestedSurvivorId,
                'duplicate_ids' => $duplicateIds,
                'duplicate_id' => count($duplicateIds) === 1 ? $duplicateIds[0] : null,
                'outcomes' => $outcomes,
                'batch_snapshot' => $batchStats,
            ],
        ]);
    }

    /**
     * Add the duplicate's email as a verified alias on the survivor unless it
     * collides with the survivor's primary email or an existing alias.
     */
    public function addAliasFromDuplicate(User $duplicate, User $survivor, ?int $adminId = null): bool
    {
        return $this->promoteDuplicateEmailToAlias($duplicate, $survivor, $adminId ?? 0);
    }

    private function promoteDuplicateEmailToAlias(User $duplicate, User $survivor, int $adminId): bool
    {
        $email = strtolower(trim((string) $duplicate->getOriginal('email')));
        if ($email === '' || $survivor->ownsEmail($email)) {
            return false;
        }

        try {
            UserEmail::create([
                'user_id' => $survivor->id,
                'email' => $email,
                'verified_at' => now(),
                'source' => UserEmail::SOURCE_MERGE,
                'added_by_user_id' => $adminId ?: null,
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function loadPair(int $survivorId, int $duplicateId): array
    {
        if ($survivorId === $duplicateId) {
            throw new RuntimeException('Survivor and duplicate must be different users.');
        }

        $survivor = User::withTrashed()->find($survivorId);
        $duplicate = User::withTrashed()->find($duplicateId);

        if (! $survivor || $survivor->trashed()) {
            throw new RuntimeException("Survivor user #{$survivorId} not found or already soft-deleted.");
        }
        if (! $duplicate || $duplicate->trashed()) {
            throw new RuntimeException("Duplicate user #{$duplicateId} not found or already merged.");
        }

        if ((int) $duplicate->id < (int) $survivor->id) {
            return [$duplicate, $survivor];
        }

        return [$survivor, $duplicate];
    }

    private function applyFieldResolutions(User $survivor, User $duplicate, array $resolutions): void
    {
        $updates = [];
        foreach (self::RESOLVABLE_FIELDS as $field) {
            if (! array_key_exists($field, $resolutions)) {
                continue;
            }
            $pick = $resolutions[$field];
            if ($pick === 'duplicate') {
                $updates[$field] = $duplicate->{$field};
            } elseif ($pick === 'survivor') {
                continue;
            } else {
                $updates[$field] = $pick;
            }
        }
        if ($updates !== []) {
            $survivor->forceFill($updates)->save();
        }
    }

    /**
     * Walk every table that has an FK to `users.id` via `user_id` or `client_id`,
     * reassign duplicate → survivor, and detect UNIQUE collisions on (other_col, fk_col).
     *
     * @param  array<int, string>  $collisions  Out-parameter (by ref): tables where we skipped rows due to UNIQUE collisions.
     * @return array<int, array{table: string, column: string, updated: int, skipped: int}>
     */
    private function reassignChildRows(int $duplicateId, int $survivorId, array &$collisions): array
    {
        $report = [];
        $tables = $this->tablesReferencingUsers();

        foreach ($tables as $table) {
            foreach (self::REASSIGN_COLUMNS as $fkCol) {
                if (! Schema::hasColumn($table, $fkCol)) {
                    continue;
                }

                $totalDuplicate = (int) DB::table($table)->where($fkCol, $duplicateId)->count();
                if ($totalDuplicate === 0) {
                    continue;
                }

                $existingSurvivorPairs = $this->uniqueOtherColumnsFor($table, $fkCol, $survivorId);
                $duplicateOtherValues = $this->otherColumnValuesFor($table, $fkCol, $duplicateId);

                $skipped = 0;
                if ($duplicateOtherValues !== []) {
                    foreach ($duplicateOtherValues as $row) {
                        $otherCol = $row['other_col'];
                        $otherVal = $row['other_val'];

                        if ($otherCol === null || $otherVal === null) {
                            continue;
                        }

                        if (in_array($otherCol, self::PROTECTED_COLUMNS, true)) {
                            continue;
                        }

                        if (isset($existingSurvivorPairs[$otherCol][(string) $otherVal])) {
                            $skipped++;
                            $collisions[] = sprintf(
                                '%s.%s already exists for survivor on %s=%s (duplicate row left intact)',
                                $table,
                                $fkCol,
                                $otherCol,
                                $otherVal
                            );

                            continue;
                        }

                        DB::table($table)
                            ->where($fkCol, $duplicateId)
                            ->where($otherCol, $otherVal)
                            ->update([$fkCol => $survivorId]);

                        $existingSurvivorPairs[$otherCol][(string) $otherVal] = true;
                    }

                    // Reassign remaining rows where the composite columns are null
                    $query = DB::table($table)->where($fkCol, $duplicateId);
                    foreach (array_keys($existingSurvivorPairs) as $otherCol) {
                        $query->whereNull($otherCol);
                    }
                    $query->update([$fkCol => $survivorId]);
                } else {
                    DB::table($table)
                        ->where($fkCol, $duplicateId)
                        ->update([$fkCol => $survivorId]);
                }

                $report[] = [
                    'table' => $table,
                    'column' => $fkCol,
                    'updated' => $totalDuplicate - $skipped,
                    'skipped' => $skipped,
                ];
            }
        }

        return $report;
    }

    private function revokeDuplicateTokens(int $duplicateId): int
    {
        $table = config('sanctum.personal_access_tokens_table', 'personal_access_tokens');
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tokenable_id')) {
            return 0;
        }

        return DB::table($table)
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $duplicateId)
            ->delete();
    }

    private function mergeRolesAndPermissions(int $duplicateId, int $survivorId): int
    {
        $count = 0;
        foreach (['model_has_roles', 'model_has_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $rows = DB::table($table)
                ->where('model_type', User::class)
                ->where('model_id', $duplicateId)
                ->get();

            foreach ($rows as $row) {
                $query = DB::table($table)
                    ->where('model_type', User::class)
                    ->where('model_id', $survivorId);

                $insertData = [
                    'model_type' => User::class,
                    'model_id' => $survivorId,
                ];

                if ($table === 'model_has_roles') {
                    $query->where('role_id', $row->role_id);
                    $insertData['role_id'] = $row->role_id;
                } else {
                    $query->where('permission_id', $row->permission_id);
                    $insertData['permission_id'] = $row->permission_id;
                }

                if (! $query->exists()) {
                    DB::table($table)->insert($insertData);
                    $count++;
                }
            }

            DB::table($table)
                ->where('model_type', User::class)
                ->where('model_id', $duplicateId)
                ->delete();
        }

        return $count;
    }

    /**
     * @return array<int, string>
     */
    private function tablesReferencingUsers(): array
    {
        $tables = collect(Schema::getTables());

        $result = [];
        foreach ($tables as $table) {
            $tableName = $this->extractTableName($table);
            if (! $tableName || $tableName === 'users') {
                continue;
            }

            if (in_array($tableName, $this->getExcludedTables(), true)) {
                continue;
            }

            foreach (self::REASSIGN_COLUMNS as $fkCol) {
                if (! Schema::hasColumn($tableName, $fkCol)) {
                    continue;
                }

                $result[$tableName] = true;
                break;
            }
        }

        return array_keys($result);
    }

    private function extractTableName(mixed $table): ?string
    {
        if (is_object($table)) {
            $name = $table->name ?? $table->Tables_in_DB ?? null;
        } elseif (is_array($table)) {
            $name = $table['name'] ?? $table['Tables_in_DB'] ?? null;
        } else {
            $name = $table;
        }

        return $name !== null && $name !== '' ? (string) $name : null;
    }

    /**
     * @return array<int, string>
     */
    private function getExcludedTables(): array
    {
        return [
            'erp_tenant_clients',
            'tenants',
            'tenant_clients',
        ];
    }

    private function uniqueOtherColumnsFor(string $table, string $fkCol, int $userId): array
    {
        $indexes = $this->compositeIndexesOn($table, $fkCol);

        $pairs = [];
        foreach ($indexes as $otherCol) {
            $values = DB::table($table)
                ->where($fkCol, $userId)
                ->whereNotNull($otherCol)
                ->pluck($otherCol)
                ->all();
            foreach ($values as $v) {
                $pairs[$otherCol][(string) $v] = true;
            }
        }

        return $pairs;
    }

    private function otherColumnValuesFor(string $table, string $fkCol, int $userId): array
    {
        $indexes = $this->compositeIndexesOn($table, $fkCol);

        $rows = [];
        foreach ($indexes as $otherCol) {
            $records = DB::table($table)
                ->where($fkCol, $userId)
                ->whereNotNull($otherCol)
                ->select($otherCol)
                ->distinct()
                ->get();
            foreach ($records as $rec) {
                $rows[] = ['other_col' => $otherCol, 'other_val' => $rec->{$otherCol}];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function compositeIndexesOn(string $table, string $fkCol): array
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            return $this->mysqlCompositeIndexesOn($table, $fkCol);
        }
        if ($driver === 'pgsql') {
            return $this->pgsqlCompositeIndexesOn($table, $fkCol);
        }
        if ($driver === 'sqlite') {
            return $this->sqliteCompositeIndexesOn($table, $fkCol);
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function sqliteCompositeIndexesOn(string $table, string $fkCol): array
    {
        $indexes = DB::select("PRAGMA index_list(\"{$table}\")");
        $result = [];

        foreach ($indexes as $idx) {
            $idxName = $idx->name ?? null;
            if (! $idxName) {
                continue;
            }
            $colsInfo = DB::select("PRAGMA index_info(\"{$idxName}\")");
            $cols = [];
            foreach ($colsInfo as $colInfo) {
                if (! empty($colInfo->name)) {
                    $cols[] = $colInfo->name;
                }
            }
            if (in_array($fkCol, $cols, true) && count($cols) > 1) {
                foreach ($cols as $c) {
                    if ($c !== $fkCol && ! in_array($c, self::PROTECTED_COLUMNS, true)) {
                        $result[$c] = true;
                    }
                }
            }
        }

        return array_keys($result);
    }

    /**
     * @return array<int, string>
     */
    private function mysqlCompositeIndexesOn(string $table, string $fkCol): array
    {
        $database = DB::connection()->getDatabaseName();
        $indexes = DB::select(
            'SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS COLS
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             GROUP BY INDEX_NAME',
            [$database, $table]
        );

        $result = [];
        foreach ($indexes as $idx) {
            $cols = array_filter(explode(',', $idx->COLS));
            if (in_array($fkCol, $cols, true) && count($cols) > 1) {
                foreach ($cols as $c) {
                    if ($c !== $fkCol && ! in_array($c, self::PROTECTED_COLUMNS, true)) {
                        $result[$c] = true;
                    }
                }
            }
        }

        return array_keys($result);
    }

    /**
     * @return array<int, string>
     */
    private function pgsqlCompositeIndexesOn(string $table, string $fkCol): array
    {
        $rows = DB::select(
            'SELECT a.attname AS column_name
             FROM pg_index i
             JOIN pg_class c ON c.oid = i.indrelid
             JOIN pg_attribute a ON a.attrelid = c.oid AND a.attnum = ANY(i.indkey)
             WHERE c.relname = ?',
            [$table]
        );

        $byIndex = [];
        $schema = DB::select(
            'SELECT indexname, indexdef FROM pg_indexes WHERE tablename = ?',
            [$table]
        );

        $result = [];
        foreach ($schema as $idx) {
            preg_match_all('/"([^"]+)"/', $idx->indexdef, $m);
            $cols = $m[1] ?? [];
            if (in_array($fkCol, $cols, true) && count($cols) > 1) {
                foreach ($cols as $c) {
                    if ($c !== $fkCol && ! in_array($c, self::PROTECTED_COLUMNS, true)) {
                        $result[$c] = true;
                    }
                }
            }
        }

        return array_keys($result);
    }

    private function collectChildCounts(int $duplicateId): array
    {
        $counts = [];
        foreach ($this->tablesReferencingUsers() as $table) {
            foreach (self::REASSIGN_COLUMNS as $fkCol) {
                if (! Schema::hasColumn($table, $fkCol)) {
                    continue;
                }
                $count = (int) DB::table($table)->where($fkCol, $duplicateId)->count();
                if ($count > 0) {
                    $counts["{$table}.{$fkCol}"] = $count;
                }
            }
        }

        return $counts;
    }
}
