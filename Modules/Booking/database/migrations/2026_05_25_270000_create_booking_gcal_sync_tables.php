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
        Schema::create('booking_google_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index()->nullable(); // the resource/staff owning the account
            $table->string('google_id')->index();
            $table->string('email');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_google_calendars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('calendar_id')->index();
            $table->string('name');
            $table->enum('sync_direction', ['two-way', 'push', 'pull'])->default('two-way');
            $table->boolean('is_active')->default(true);
            $table->string('sync_token')->nullable(); // Used for incremental sync
            $table->timestamps();
            
            $table->foreign('account_id')->references('id')->on('booking_google_accounts')->onDelete('cascade');
        });

        Schema::create('booking_google_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('calendar_id')->index()->nullable();
            $table->enum('direction', ['push', 'pull']);
            $table->enum('status', ['success', 'failed']);
            $table->integer('events_synced')->default(0);
            $table->text('error_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_google_sync_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('booking_id')->index();
            $table->string('google_event_id')->index();
            $table->unsignedBigInteger('calendar_id')->index();
            $table->timestamps();
            
            $table->unique(['booking_id', 'google_event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_google_sync_events');
        Schema::dropIfExists('booking_google_sync_logs');
        Schema::dropIfExists('booking_google_calendars');
        Schema::dropIfExists('booking_google_accounts');
    }
};
