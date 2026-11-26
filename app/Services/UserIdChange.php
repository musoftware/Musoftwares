<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Gives an account another ID, for example a free 1 for the owner. Every row that points at the account moves with
 * it in one transaction: columns with a foreign key to users.id, user_id and client_id columns, and polymorphic
 * columns that name the User model. The new ID must never have been used, deleted accounts included, with one
 * exception: an old copy of this account that was merged into it. The merge kept the newer ID, so the account takes
 * its old ID back; the merged copy (soft deleted, never removed) and anything still pointing at it move to a new ID.
 */
class UserIdChange
{
    /** Tables whose user_id belongs to another system's users, as in the user merge. */
    private const OTHER_USERS_TABLES = ['erp_tenant_clients', 'tenants', 'tenant_clients'];

    /** Columns that point at an account even where the table has no foreign key. */
    private const USER_COLUMNS = ['user_id', 'client_id'];

    /**
     * Columns the models point at an account with that may have no foreign key in the database (older tables).
     * Read from the models' belongsTo(User::class, ...) and the User model's own relations.
     */
    private const MODEL_COLUMNS = [
        'admin_audit_logs' => ['actor_user_id'],
        'builder_projects' => ['referrer_user_id'],
        'campaigns' => ['created_by'],
        'comments' => ['commenter_id'],
        'coworker_messages' => ['admin_id'],
        'data_subject_requests' => ['processed_by_admin_id'],
        'earnings' => ['referred_user_id'],
        'impersonation_logs' => ['impersonator_id', 'impersonated_id'],
        'invoice_cost_lines' => ['credit_user_id'],
        'invoices' => ['cost_payable_user_id'],
        'kyc_documents' => ['reviewed_by'],
        'marketplace_custom_offers' => ['seller_id', 'buyer_id'],
        'marketplace_order_revisions' => ['buyer_id'],
        'marketplace_order_status_histories' => ['changed_by'],
        'marketplace_orders' => ['buyer_id', 'seller_id'],
        'marketplace_service_serials' => ['used_by'],
        'marketplace_services' => ['seller_id'],
        'messages' => ['sender_id'],
        'project_admin_notes' => ['author_id'],
        'project_board_notes' => ['author_id'],
        'project_comments' => ['author_id'],
        'project_files' => ['uploaded_by'],
        'project_reports' => ['author_id'],
        'projects' => ['owner_id'],
        'quotations' => ['created_by_user_id'],
        'serial_device_trial_logs' => ['reseller_id', 'customer_user_id'],
        'serial_user_devices' => ['reseller_id'],
        'service_playbooks' => ['created_by'],
        'service_reviews' => ['reviewer_id', 'seller_id'],
        'shortlink_links' => ['created_by_user_id'],
        'task_audit_logs' => ['changed_by'],
        'tasks' => ['ignored_by'],
        'tenant_usages' => ['tenant_id'],
        'tickets' => ['assigned_employee_id', 'quoted_by'],
        'user_credentials' => ['admin_id'],
        'user_emails' => ['added_by_user_id'],
        'users' => ['kyc_verified_by', 'ref_user_id', 'merged_into_user_id'],
        'user_threads' => ['user1_id', 'user2_id'],
        'wallet_transfers' => ['sender_id', 'receiver_id'],
    ];

    /** How a polymorphic column names the User model (see the morph map). */
    private const USER_MORPH_TYPES = [User::class, 'direct_message'];

    public function isFree(int $id): bool
    {
        return $id > 0 && ! User::withTrashed()->whereKey($id)->exists();
    }

    /** The ID was never used, or only by an old copy of this account that was merged into it. */
    public function canTake(User $user, int $id): bool
    {
        return $id !== (int) $user->id && ($this->isFree($id) || $this->mergedCopyHolding($user, $id) !== null);
    }

    /**
     * What would move with the account: one row per column that points at it now.
     *
     * @return list<array{table: string, column: string, rows: int}>
     */
    public function preview(int $userId): array
    {
        $columns = $this->columns();
        if ($columns->isEmpty()) {
            return [];
        }
        // a few union queries for all the counts: the page timed out on the live server with one count per column.
        // Groups of 200 stay under SQLite's limit of 500 selects in one union.
        $counts = $columns
            ->map(fn (array $column, int $index) => $this->pointingAt($column, $userId)->selectRaw('? as idx, count(*) as aggregate', [$index]))
            ->chunk(200)
            ->flatMap(fn (Collection $queries) => $queries->reduce(fn ($union, $query) => $union ? $union->unionAll($query) : $query)->get())
            ->pluck('aggregate', 'idx');

        return $columns
            ->map(fn (array $column, int $index) => ['table' => $column['table'], 'column' => $column['column'], 'rows' => (int) ($counts[$index] ?? 0)])
            ->filter(fn (array $column) => $column['rows'] > 0)
            ->values()
            ->all();
    }

    /** @return list<array{table: string, column: string, rows: int}> what moved */
    public function change(User $user, int $newId, int $adminId): array
    {
        $oldId = (int) $user->id;
        if (! $this->canTake($user, $newId)) {
            throw new InvalidArgumentException("The ID {$newId} is taken or was used before.");
        }
        $copy = $this->mergedCopyHolding($user, $newId);
        [$moved, $copyMovedTo] = DB::transaction(function () use ($copy, $oldId, $newId): array {
            $this->pauseForeignKeys();
            try {
                $copyMovedTo = $copy ? $this->moveId((int) $copy->id, (int) User::withTrashed()->max('id') + 1)['to'] : null;

                return [$this->moveId($oldId, $newId)['moved'], $copyMovedTo];
            } finally {
                $this->resumeForeignKeys();
            }
        });
        AdminAuditLog::create([
            'actor_user_id' => $adminId,
            'action' => 'users.id_changed',
            'severity' => AdminAuditLog::SEVERITY_WARNING,
            'target_type' => User::class,
            'target_id' => $newId,
            'meta' => ['old_id' => $oldId, 'new_id' => $newId, 'moved' => $moved, 'merged_copy_moved_to' => $copyMovedTo],
        ]);

        return $moved;
    }

    /** A soft-deleted account at this ID that was merged into $user. */
    private function mergedCopyHolding(User $user, int $id): ?User
    {
        return User::onlyTrashed()->whereKey($id)->where('merged_into_user_id', $user->id)->first();
    }

    /** @return array{to: int, moved: list<array{table: string, column: string, rows: int}>} */
    private function moveId(int $from, int $to): array
    {
        $moved = $this->columns()
            ->map(fn (array $column) => ['table' => $column['table'], 'column' => $column['column'], 'rows' => $this->pointingAt($column, $from)->update([$column['column'] => $to])])
            ->filter(fn (array $column) => $column['rows'] > 0)
            ->values()
            ->all();
        DB::table('users')->where('id', $from)->update(['id' => $to]);

        return ['to' => $to, 'moved' => $moved];
    }

    /**
     * Every column in the database that can point at an account.
     *
     * @return Collection<int, array{table: string, column: string, type_column: ?string}>
     */
    private function columns(): Collection
    {
        return collect($this->schema())
            ->reject(fn (array $_, string $name) => in_array($name, self::OTHER_USERS_TABLES, true))
            ->flatMap(fn (array $table, string $name) => $this->columnsOf($name, $table['columns'], $table['user_keys']))
            ->unique(fn (array $column) => $column['table'].'.'.$column['column'])
            ->values();
    }

    /**
     * Every table with its columns and its single-column foreign keys to users.id.
     * MySQL reads it in two queries; asking table by table took ~600 slow schema queries and timed out the page.
     *
     * @return array<string, array{columns: list<string>, user_keys: list<string>}>
     */
    private function schema(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return collect(Schema::getTables())
                ->map(fn ($table) => is_array($table) ? $table['name'] : $table->name)
                ->mapWithKeys(fn (string $name) => [$name => ['columns' => Schema::getColumnListing($name), 'user_keys' => $this->userKeysOf($name)]])
                ->all();
        }
        $columns = DB::select("select c.table_name as t, c.column_name as c from information_schema.columns c join information_schema.tables b on b.table_schema = c.table_schema and b.table_name = c.table_name where c.table_schema = database() and b.table_type = 'BASE TABLE' order by c.table_name, c.ordinal_position");
        $keys = DB::select("select table_name as t, column_name as c from information_schema.key_column_usage where table_schema = database() and referenced_table_name = 'users' and referenced_column_name = 'id'");
        $keyed = collect($keys)->groupBy('t')->map(fn ($rows) => $rows->pluck('c')->all());

        return collect($columns)->groupBy('t')
            ->map(fn ($rows, string $name) => ['columns' => $rows->pluck('c')->all(), 'user_keys' => $keyed[$name] ?? []])
            ->all();
    }

    /** @return list<string> */
    private function userKeysOf(string $table): array
    {
        return collect(Schema::getForeignKeys($table))
            ->filter(fn (array $key) => $key['foreign_table'] === 'users' && $key['foreign_columns'] === ['id'] && count($key['columns']) === 1)
            ->map(fn (array $key) => $key['columns'][0])
            ->values()
            ->all();
    }

    /** @return list<array{table: string, column: string, type_column: ?string}> */
    private function columnsOf(string $table, array $names, array $userKeys): array
    {
        $keyed = collect($userKeys);
        $named = array_intersect([...($table === 'users' ? [] : self::USER_COLUMNS), ...(self::MODEL_COLUMNS[$table] ?? [])], $names);
        $plain = $keyed->merge($named)->unique()->map(fn (string $column) => ['table' => $table, 'column' => $column, 'type_column' => null]);
        $morphs = collect($names)
            ->filter(fn (string $column) => str_ends_with($column, '_type') && in_array(substr($column, 0, -5).'_id', $names, true))
            ->map(fn (string $column) => ['table' => $table, 'column' => substr($column, 0, -5).'_id', 'type_column' => $column]);

        return $plain->merge($morphs)->values()->all();
    }

    /**
     * The ID is compared as text: some of these columns are text (charity_transactions.reference_id holds 'admin_69'),
     * and MySQL in strict mode refuses an update that compares a text column with a number. Number columns still match.
     */
    private function pointingAt(array $column, int $userId)
    {
        return DB::table($column['table'])
            ->where($column['column'], (string) $userId)
            ->when($column['type_column'], fn ($query, string $type) => $query->whereIn($type, self::USER_MORPH_TYPES));
    }

    /** The rows move one table at a time, so for a moment some point at an ID the users table does not have yet. */
    private function pauseForeignKeys(): void
    {
        DB::statement(DB::getDriverName() === 'sqlite' ? 'PRAGMA defer_foreign_keys = ON' : 'SET FOREIGN_KEY_CHECKS=0');
    }

    private function resumeForeignKeys(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
