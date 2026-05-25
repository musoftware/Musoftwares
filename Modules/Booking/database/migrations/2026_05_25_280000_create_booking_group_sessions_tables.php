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
        if (!Schema::hasTable('booking_group_sessions')) {
            Schema::create('booking_group_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('resource_id')->index()->nullable(); // the staff member/instructor
                $table->string('title');
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->integer('max_capacity');
                $table->integer('min_capacity')->default(1);
                $table->decimal('price', 10, 2)->default(0.00);
                $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('booking_group_session_participants')) {
            Schema::create('booking_group_session_participants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('group_session_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->enum('status', ['confirmed', 'cancelled', 'no-show', 'attended'])->default('confirmed');
                $table->timestamps();
                
                $table->foreign('group_session_id')->references('id')->on('booking_group_sessions')->onDelete('cascade');
                $table->unique(['group_session_id', 'customer_id'], 'booking_gs_part_unique'); // Shorter name to prevent MySQL identifier too long error
            });
        }

        if (!Schema::hasTable('booking_group_session_waitlists')) {
            Schema::create('booking_group_session_waitlists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('group_session_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->timestamp('joined_at')->useCurrent();
                $table->enum('status', ['waiting', 'promoted', 'expired'])->default('waiting');
                $table->timestamps();
                
                $table->foreign('group_session_id')->references('id')->on('booking_group_sessions')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('booking_group_session_logs')) {
            Schema::create('booking_group_session_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('group_session_id')->index();
                $table->string('action'); // e.g. participant_joined, waitlist_promoted
                $table->text('description')->nullable();
                $table->timestamps();
                
                $table->foreign('group_session_id')->references('id')->on('booking_group_sessions')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_group_session_logs');
        Schema::dropIfExists('booking_group_session_waitlists');
        Schema::dropIfExists('booking_group_session_participants');
        Schema::dropIfExists('booking_group_sessions');
    }
};
