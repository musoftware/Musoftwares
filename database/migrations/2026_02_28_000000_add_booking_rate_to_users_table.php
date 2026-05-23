<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Booking rate = سعر الحجز للمهام (focus/task page only). Separate from hour_rate (invoices/timers).
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('booking_rate', 12, 2)->nullable()->after('hour_rate_currency');
            $table->unsignedBigInteger('booking_rate_currency')->nullable()->after('booking_rate');
            $table->date('booking_rate_expires_at')->nullable()->after('booking_rate_currency');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('booking_rate_currency')->references('id')->on('currencies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['booking_rate_currency']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['booking_rate', 'booking_rate_currency', 'booking_rate_expires_at']);
        });
    }
};
