<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->json('country_codes')->nullable()->after('string_format');
            $table->boolean('is_default')->default(false)->after('country_codes');
        });

        // Set default values for existing currencies
        DB::table('currencies')->where('currency', 'USD')->update([
            'country_codes' => json_encode(['US', 'CA']),
            'is_default' => true,
        ]);

        DB::table('currencies')->where('currency', 'EGP')->update([
            'country_codes' => json_encode(['EG']),
            'is_default' => false,
        ]);

        DB::table('currencies')->where('currency', 'SAR')->update([
            'country_codes' => json_encode(['SA']),
            'is_default' => false,
        ]);

        DB::table('currencies')->where('currency', 'AED')->update([
            'country_codes' => json_encode(['AE']),
            'is_default' => false,
        ]);

        DB::table('currencies')->where('currency', 'EUR')->update([
            'country_codes' => json_encode(['DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'FI', 'GR', 'IE', 'PT']),
            'is_default' => false,
        ]);

        DB::table('currencies')->where('currency', 'GBP')->update([
            'country_codes' => json_encode(['GB']),
            'is_default' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['country_codes', 'is_default']);
        });
    }
};
