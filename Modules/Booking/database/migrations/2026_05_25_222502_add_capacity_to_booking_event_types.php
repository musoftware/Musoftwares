<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_event_types') && !Schema::hasColumn('booking_event_types', 'is_group_session')) {
            Schema::table('booking_event_types', function (Blueprint $table) {
                $table->boolean('is_group_session')->default(false)->after('title');
                $table->integer('capacity')->default(1)->after('is_group_session');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_event_types') && Schema::hasColumn('booking_event_types', 'is_group_session')) {
            Schema::table('booking_event_types', function (Blueprint $table) {
                $table->dropColumn(['is_group_session', 'capacity']);
            });
        }
    }
};
