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
        Schema::create('booking_recurring_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('resource_id')->index()->nullable();
            $table->unsignedBigInteger('service_id')->index()->nullable();
            $table->string('rrule'); // The iCal recurrence rule string
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });

        Schema::create('booking_recurring_exceptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('series_id')->index();
            $table->date('exception_date');
            $table->string('reason')->nullable(); // holiday, manually skipped, moved
            $table->enum('status', ['skipped', 'rescheduled'])->default('skipped');
            $table->timestamps();

            $table->foreign('series_id')->references('id')->on('booking_recurring_series')->onDelete('cascade');
        });

        Schema::create('booking_recurring_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('series_id')->index();
            $table->string('action');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('series_id')->references('id')->on('booking_recurring_series')->onDelete('cascade');
        });

        // Add series_id to the core bookings table
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('recurring_series_id')->nullable()->index()->after('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('recurring_series_id');
        });
        
        Schema::dropIfExists('booking_recurring_logs');
        Schema::dropIfExists('booking_recurring_exceptions');
        Schema::dropIfExists('booking_recurring_series');
    }
};
