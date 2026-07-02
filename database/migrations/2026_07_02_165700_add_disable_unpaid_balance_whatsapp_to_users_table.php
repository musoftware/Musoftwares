<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'disable_unpaid_balance_whatsapp')) {
                $table->boolean('disable_unpaid_balance_whatsapp')
                    ->default(false)
                    ->after('whatsapp_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'disable_unpaid_balance_whatsapp')) {
                $table->dropColumn('disable_unpaid_balance_whatsapp');
            }
        });
    }
};