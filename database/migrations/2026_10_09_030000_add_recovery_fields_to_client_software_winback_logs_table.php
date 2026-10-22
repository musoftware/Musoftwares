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
        Schema::table('client_software_winback_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('client_software_winback_logs', 'status')) {
                $table->string('status')->default('sent')->index()->after('reminder_number');
            }
            if (! Schema::hasColumn('client_software_winback_logs', 'recovered_at')) {
                $table->timestamp('recovered_at')->nullable()->after('sent_at');
            }
            if (! Schema::hasColumn('client_software_winback_logs', 'recovery_event_type')) {
                $table->string('recovery_event_type')->nullable()->after('recovered_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_software_winback_logs', function (Blueprint $table) {
            $columns = ['status', 'recovered_at', 'recovery_event_type'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('client_software_winback_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
