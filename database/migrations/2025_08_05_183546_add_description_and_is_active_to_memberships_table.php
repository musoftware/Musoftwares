<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            if (!Schema::hasColumn('memberships', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('memberships', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('memberships', 'description')) {
                $columns[] = 'description';
            }
            if (Schema::hasColumn('memberships', 'is_active')) {
                $columns[] = 'is_active';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
