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
        // 1. booking_queues
        if (!Schema::hasTable('booking_queues')) {
            Schema::create('booking_queues', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index(); // For branch-specific queues
                $table->unsignedBigInteger('resource_id')->nullable()->index(); // For doctor-specific queues
                
                $table->string('name');
                $table->string('prefix')->nullable()->comment('E.g., CL, ROOM1');
                $table->boolean('is_active')->default(true);
                
                // Track current daily sequence
                $table->date('current_sequence_date')->nullable();
                $table->integer('current_sequence_number')->default(0);

                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. booking_queue_entries
        if (!Schema::hasTable('booking_queue_entries')) {
            Schema::create('booking_queue_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('queue_id')->index();
                $table->unsignedBigInteger('booking_id')->nullable()->index()->comment('Null for walk-ins');
                
                $table->string('walkin_name')->nullable();
                $table->string('walkin_phone')->nullable();

                // e.g. A-1, CL-55
                $table->string('token_number')->index();
                $table->integer('sequence_number'); // The raw integer for sorting
                
                // waiting, called, serving, completed, skipped, cancelled, no_show
                $table->string('status')->default('waiting')->index();
                
                // vip, emergency, appointment, walk_in
                $table->integer('priority_level')->default(0)->index()->comment('Higher is more urgent');
                
                $table->integer('wait_time_estimate_minutes')->nullable();

                // Timestamps for tracking flow
                $table->timestamp('checked_in_at')->nullable()->index();
                $table->timestamp('called_at')->nullable();
                $table->timestamp('serving_at')->nullable();
                $table->timestamp('completed_at')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('queue_id')->references('id')->on('booking_queues')->onDelete('cascade');
            });
        }

        // 3. booking_queue_displays
        if (!Schema::hasTable('booking_queue_displays')) {
            Schema::create('booking_queue_displays', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('queue_id')->index();
                
                $table->string('tv_name');
                $table->string('display_key')->unique()->comment('Unguessable URL hash');
                $table->json('theme_settings')->nullable();
                
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('queue_id')->references('id')->on('booking_queues')->onDelete('cascade');
            });
        }

        // 4. booking_queue_logs (Audit Trail)
        if (!Schema::hasTable('booking_queue_logs')) {
            Schema::create('booking_queue_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('queue_entry_id')->index();
                
                $table->string('action')->comment('created, called, skipped, etc.');
                $table->unsignedBigInteger('performed_by_user_id')->nullable()->index();
                $table->text('notes')->nullable();
                
                $table->timestamps();

                $table->foreign('queue_entry_id')->references('id')->on('booking_queue_entries')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_queue_logs');
        Schema::dropIfExists('booking_queue_displays');
        Schema::dropIfExists('booking_queue_entries');
        Schema::dropIfExists('booking_queues');
    }
};
