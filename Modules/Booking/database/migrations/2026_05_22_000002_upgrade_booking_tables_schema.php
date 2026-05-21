<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('booking_provider_id')->nullable()->after('booking_event_type_id')->constrained('booking_providers')->nullOnDelete();
        });

        Schema::table('booking_availability_rules', function (Blueprint $table) {
            $table->foreignId('booking_provider_id')->nullable()->after('id')->constrained('booking_providers')->cascadeOnDelete();
            $table->string('type')->default('recurring')->after('booking_provider_id'); // 'recurring' or 'one-time'
            $table->date('date')->nullable()->after('type'); // date if one-time
            
            $table->unsignedBigInteger('booking_event_type_id')->nullable()->change();
            $table->tinyInteger('weekday')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_provider_id');
        });

        Schema::table('booking_availability_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_provider_id');
            $table->dropColumn(['type', 'date']);
        });
    }
};
