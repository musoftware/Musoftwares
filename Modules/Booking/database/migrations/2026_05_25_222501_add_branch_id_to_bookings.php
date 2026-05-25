<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings') && !Schema::hasColumn('bookings', 'branch_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->index()->after('tenant_id');
                // The foreign key constraint could be added here, but for module loosely-coupled design we often omit strict FKs
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'branch_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }
    }
};
