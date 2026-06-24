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
        $prefixes = [
            'erp_',
            'gold_',
            'crm_',
            'freelance_',
            'affiliate_pos_',
            'booking_'
        ];

        $tables = array_column(Schema::getTables(), 'name');
        
        Schema::disableForeignKeyConstraints();
        
        foreach ($tables as $table) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($table, $prefix)) {
                    Schema::dropIfExists($table);
                    break;
                }
            }
            if ($table === 'bookings') {
                Schema::dropIfExists($table);
            }
        }
        
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration
    }
};
