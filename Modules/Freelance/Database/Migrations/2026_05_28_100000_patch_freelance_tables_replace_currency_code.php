<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $tables = [
            'point_packages',
            'freelance_jobs',
            'freelance_proposals',
            'freelance_contracts',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'currency_code')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('restrict');
                });

                // Update data based on currency_code
                DB::table($table)->where('currency_code', 'USD')->update(['currency_id' => 1]);
                DB::table($table)->where('currency_code', 'EUR')->update(['currency_id' => 3]);
                DB::table($table)->where('currency_code', 'GBP')->update(['currency_id' => 4]);
                DB::table($table)->where('currency_code', 'AED')->update(['currency_id' => 5]);
                // Default fallback to EGP (2)
                DB::table($table)->whereNull('currency_id')->update(['currency_id' => 2]);

                // Make currency_id non-nullable if necessary, but we can leave it as is or enforce it if data is clean
                // Now drop currency_code
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('currency_code');
                });
            }
        }
    }

    public function down()
    {
        // No going back
    }
};
