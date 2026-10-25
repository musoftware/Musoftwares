<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026_05_24_000004 renamed `currency` -> `currency_id` (and friends) only where the
 * new column did not exist yet. On production many tables already had both, so the
 * legacy column stayed behind as NOT NULL without a default. Code now writes only the
 * `*_id` column, so every insert into those tables failed (coupons, earnings,
 * vouchers, referral commissions, ...).
 *
 * This makes the legacy column nullable wherever both columns exist. Nothing is
 * dropped and no data changes; removing the legacy columns is a separate decision.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->legacyPairs() as [$table, $old, $new]) {
            $column = $this->column($table, $old);
            if (! $column || $column->IS_NULLABLE === 'YES' || ! Schema::hasColumn($table, $new)) {
                continue;
            }

            $default = $column->COLUMN_DEFAULT === null ? 'NULL' : DB::getPdo()->quote(trim($column->COLUMN_DEFAULT, "'"));
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$old}` {$column->COLUMN_TYPE} NULL DEFAULT {$default}");
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: re-adding NOT NULL would break inserts again.
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function legacyPairs(): array
    {
        $rename = require __DIR__.'/2026_05_24_000004_rename_currency_columns_to_id.php';
        $property = new ReflectionProperty($rename, 'tablesToRename');

        $pairs = [];
        foreach ($property->getValue($rename) as $table => $columns) {
            foreach ($columns as $old => $new) {
                $pairs[] = [$table, $old, $new];
            }
        }

        return $pairs;
    }

    private function column(string $table, string $name): ?object
    {
        return DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $table, $name]
        );
    }
};
