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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('whatsapp_balance_egp', 10, 2)->default(0)->after('currency');
            $table->timestamp('whatsapp_balance_reset_date')->nullable()->after('whatsapp_balance_egp');

            $table->index(['whatsapp_balance_egp']);
            $table->index(['whatsapp_balance_reset_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_balance_egp']);
            $table->dropIndex(['whatsapp_balance_reset_date']);
            $table->dropColumn(['whatsapp_balance_egp', 'whatsapp_balance_reset_date']);
        });
    }
};
