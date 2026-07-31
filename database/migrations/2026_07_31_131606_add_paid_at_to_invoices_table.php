<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable()->after('status');
        });

        // Populate paid_at for existing paid invoices
        DB::table('invoices')
            ->where('status', 'paid')
            ->whereNull('paid_at')
            ->update([
                'paid_at' => DB::raw("COALESCE(updated_at, created_at)")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
