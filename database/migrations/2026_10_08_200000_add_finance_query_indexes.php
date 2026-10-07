<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the hot finance queries (invoice lists, overdue checks,
 * earnings clearing, per-user transaction lookups by reason).
 * Each index is skipped when a column is missing or an index with the
 * same leading columns already exists.
 */
return new class extends Migration
{
    private const INDEXES = [
        ['invoices', ['job_status'], 'idx_invoices_job_status'],
        ['invoices', ['due_date'], 'idx_invoices_due_date'],
        ['invoices', ['user_id', 'status'], 'idx_invoices_user_status'],
        ['earnings', ['transaction_id', 'convert_to_balance_on'], 'idx_earnings_transaction_convert_on'],
        ['transactions', ['user_id', 'reason'], 'idx_transactions_user_reason'],
    ];

    /** Prefix length used when a TEXT column is indexed on MySQL. */
    private const TEXT_PREFIX_LENGTH = 191;

    public function up(): void
    {
        foreach (self::INDEXES as [$table, $columns, $name]) {
            if (! $this->canAddIndex($table, $columns, $name)) {
                continue;
            }

            $this->addIndex($table, $columns, $name);
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as [$table, , $name]) {
            if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
        }
    }

    private function canAddIndex(string $table, array $columns, string $name): bool
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns)) {
            return false;
        }

        if (Schema::hasIndex($table, $name)) {
            return false;
        }

        return ! $this->hasEquivalentIndex($table, $columns);
    }

    /**
     * True when an existing index starts with exactly these columns in this order.
     */
    private function hasEquivalentIndex(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (array_slice($index['columns'], 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (DB::getDriverName() !== 'mysql' || ! $this->hasTextColumn($table, $columns)) {
            Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));

            return;
        }

        // MySQL cannot index TEXT columns without a prefix length.
        $parts = array_map(
            fn (string $column) => $this->isTextColumn($table, $column)
                ? "`{$column}`(".self::TEXT_PREFIX_LENGTH.')'
                : "`{$column}`",
            $columns
        );
        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` (".implode(', ', $parts).')');
    }

    private function hasTextColumn(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if ($this->isTextColumn($table, $column)) {
                return true;
            }
        }

        return false;
    }

    private function isTextColumn(string $table, string $column): bool
    {
        return in_array(Schema::getColumnType($table, $column), ['text', 'mediumtext', 'longtext', 'tinytext'], true);
    }
};
