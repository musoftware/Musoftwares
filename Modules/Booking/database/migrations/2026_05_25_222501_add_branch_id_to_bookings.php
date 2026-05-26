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
                if (Schema::hasColumn('bookings', 'tenant_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->index()->after('tenant_id');
                } else {
                    $table->unsignedBigInteger('branch_id')->nullable()->index();
                }
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
