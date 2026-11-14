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
 * columns that name the User model. The new ID must never have been used, deleted accounts included.
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
        'users' => ['kyc_verified_by', 'ref_user_id'],
        'user_threads' => ['user1_id', 'user2_id'],
        'wallet_transfers' => ['sender_id', 'receiver_id'],
    ];

    /** How a polymorphic column names the User model (see the morph map). */
    private const USER_MORPH_TYPES = [User::class, 'direct_message'];

    public function isFree(int $id): bool
    {
        return $id > 0 && ! User::withTrashed()->whereKey($id)->exists();
    }

    /**
     * What would move with the account: one row per column that points at it now.
     *
     * @return list<array{table: string, column: string, rows: int}>
     */
    public function preview(int $userId): array
    {
        return $this->columns()
            ->map(fn (array $column) => $column + ['rows' => $this->pointingAt($column, $userId)->count()])
            ->filter(fn (array $column) => $column['rows'] > 0)
            ->map(fn (array $column) => ['table' => $column['table'], 'column' => $column['column'], 'rows' => $column['rows']])
            ->values()
            ->all();
    }

    /** @return list<array{table: string, column: string, rows: int}> what moved */
    public function change(User $user, int $newId, int $adminId): array
    {
        $oldId = (int) $user->id;
        if ($newId === $oldId || ! $this->isFree($newId)) {
            throw new InvalidArgumentException("The ID {$newId} is taken or was used before.");
        }
        $moved = DB::transaction(function () use ($oldId, $newId): array {
            $this->pauseForeignKeys();
            try {
                $moved = $this->columns()
                    ->map(fn (array $column) => ['table' => $column['table'], 'column' => $column['column'], 'rows' => $this->pointingAt($column, $oldId)->update([$column['column'] => $newId])])
                    ->filter(fn (array $column) => $column['rows'] > 0)
                    ->values()
                    ->all();
                DB::table('users')->where('id', $oldId)->update(['id' => $newId]);

                return $moved;
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
            'meta' => ['old_id' => $oldId, 'new_id' => $newId, 'moved' => $moved],
        ]);

        return $moved;
    }

    /**
     * Every column in the database that can point at an account.
     *
     * @return Collection<int, array{table: string, column: string, type_column: ?string}>
     */
    private function columns(): Collection
    {
        return collect(Schema::getTables())
            ->map(fn ($table) => is_array($table) ? $table['name'] : $table->name)
            ->reject(fn (string $table) => in_array($table, self::OTHER_USERS_TABLES, true))
            ->flatMap(fn (string $table) => $this->columnsOf($table))
            ->unique(fn (array $column) => $column['table'].'.'.$column['column'])
            ->values();
    }

    /** @return list<array{table: string, column: string, type_column: ?string}> */
    private function columnsOf(string $table): array
    {
        $names = Schema::getColumnListing($table);
        $keyed = collect(Schema::getForeignKeys($table))
            ->filter(fn (array $key) => $key['foreign_table'] === 'users' && $key['foreign_columns'] === ['id'] && count($key['columns']) === 1)
            ->map(fn (array $key) => $key['columns'][0]);
        $named = array_intersect([...($table === 'users' ? [] : self::USER_COLUMNS), ...(self::MODEL_COLUMNS[$table] ?? [])], $names);
        $plain = $keyed->merge($named)->unique()->map(fn (string $column) => ['table' => $table, 'column' => $column, 'type_column' => null]);
        $morphs = collect($names)
            ->filter(fn (string $column) => str_ends_with($column, '_type') && in_array(substr($column, 0, -5).'_id', $names, true))
            ->map(fn (string $column) => ['table' => $table, 'column' => substr($column, 0, -5).'_id', 'type_column' => $column]);

        return $plain->merge($morphs)->values()->all();
    }

    private function pointingAt(array $column, int $userId)
    {
        return DB::table($column['table'])
            ->where($column['column'], $userId)
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
