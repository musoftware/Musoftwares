<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_calendar_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('type')->default('event'); // event, meeting, task
            $table->string('status')->default('scheduled');
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_calendar_meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('event_id')->constrained('erp_calendar_events')->cascadeOnDelete();
            $table->uuid('organizer_id');
            $table->string('meeting_url')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_calendar_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('erp_tenants')->cascadeOnDelete();
            $table->foreignUuid('event_id')->constrained('erp_calendar_events')->cascadeOnDelete();
            $table->timestamp('remind_at');
            $table->string('method')->default('email'); // email, notification
            $table->boolean('is_sent')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_calendar_reminders');
        Schema::dropIfExists('erp_calendar_meetings');
        Schema::dropIfExists('erp_calendar_events');
    }
};
