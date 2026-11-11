<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** When the customer closed the page with a price open, and when the discount reminder was sent to them. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->timestamp('left_price_at')->nullable()->after('leave_discount_at');
            $table->timestamp('leave_reminder_at')->nullable()->after('left_price_at');
        });
    }

    public function down(): void
    {
        Schema::table('builder_projects', function (Blueprint $table) {
            $table->dropColumn(['left_price_at', 'leave_reminder_at']);
        });
    }
};
