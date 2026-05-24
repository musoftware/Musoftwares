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
        Schema::table('booking_blocked_dates', function (Blueprint $table) {
            // Drop user_id if it exists, replace with provider_id
            if (Schema::hasColumn('booking_blocked_dates', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            
            $table->foreignId('booking_provider_id')->after('id')->constrained('booking_providers')->cascadeOnDelete();
            
            // Starts_at and ends_at already exist, we'll keep them but might make them nullable for recurring
            $table->dateTime('starts_at')->nullable()->change();
            $table->dateTime('ends_at')->nullable()->change();
            
            $table->boolean('is_recurring')->default(false)->after('reason');
            $table->string('recurring_pattern')->nullable()->after('is_recurring'); // e.g. "weekly_friday"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_blocked_dates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_provider_id');
            $table->dropColumn(['is_recurring', 'recurring_pattern']);
            // we won't restore user_id in down since it requires a valid user
        });
    }
};
