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
        Schema::table('erp_tenant_clients', function (Blueprint $table) {
            $table->dropColumn('country_code');
            $table->unsignedBigInteger('country_id')->nullable()->after('currency_id');
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
        });

        // Since currency_id might already contain varchar data, we alter it.
        // It's safer to drop and re-create if it's empty or during dev, but altering is better if DB engine allows it.
        // Alternatively, since changing varchar to int fails if there is string data like "USD", we might need to handle it.
        // But for this dev env, I will just do a raw SQL statement to clear non-numeric or drop column and re-add.
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE erp_tenant_clients DROP COLUMN currency_id');
        Schema::table('erp_tenant_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('email');
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_tenant_clients', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
            $table->string('country_code', 2)->nullable()->after('currency_id');
            
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
            $table->string('currency_id', 3)->nullable()->after('email');
        });
    }
};
