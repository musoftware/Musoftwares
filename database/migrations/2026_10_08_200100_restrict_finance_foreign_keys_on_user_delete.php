<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Invoices and earnings are money records. A hard delete of a user (or invoice)
 * must never silently wipe them, so their foreign keys move from CASCADE to RESTRICT.
 * Users and invoices use SoftDeletes, so normal deletes are not affected.
 *
 * SQLite (tests) cannot alter foreign keys in place, so it is skipped there.
 */
return new class extends Migration
{
    private const FOREIGN_KEYS = [
        ['invoices', 'user_id', 'users'],
        ['earnings', 'user_id', 'users'],
        ['earnings', 'referred_user_id', 'users'],
        ['earnings', 'referred_invoice_id', 'invoices'],
    ];

    public function up(): void
    {
        $this->setOnDelete('restrict');
    }

    public function down(): void
    {
        $this->setOnDelete('cascade');
    }

    private function setOnDelete(string $action): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Log::info('Skipping foreign key change on SQLite', ['action' => $action]);

            return;
        }

        foreach (self::FOREIGN_KEYS as [$table, $column, $foreignTable]) {
            $this->replaceForeignKey($table, $column, $foreignTable, $action);
        }
    }

    private function replaceForeignKey(string $table, string $column, string $foreignTable, string $action): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $existing = $this->findForeignKey($table, $column);

        Schema::table($table, function (Blueprint $t) use ($existing, $column, $foreignTable, $action) {
            if ($existing !== null) {
                $t->dropForeign($existing['name']);
            }

            $t->foreign($column)->references('id')->on($foreignTable)->onDelete($action);
        });
    }

    private function findForeignKey(string $table, string $column): ?array
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if ($foreignKey['columns'] === [$column]) {
                return $foreignKey;
            }
        }

        return null;
    }
};
