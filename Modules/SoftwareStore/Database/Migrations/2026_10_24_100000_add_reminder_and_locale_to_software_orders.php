<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('software_orders', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('software_orders', 'locale')) {
                $table->string('locale', 5)->nullable()->after('client_whatsapp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('software_orders', function (Blueprint $table) {
            foreach (['reminder_sent_at', 'locale'] as $column) {
                if (Schema::hasColumn('software_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
