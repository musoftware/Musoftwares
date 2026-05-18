<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_events', function (Blueprint $table) {
            $table->id();

            // Actor — who triggered this (null = system/automation)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Subject — what entity this activity is about (morphable)
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Event key (machine-readable, dot-notation)
            // e.g. invoice.paid, task.completed, booking.created
            $table->string('event')->index();

            // Human-readable sentence shown in the timeline
            $table->string('description');

            // Extra structured context (amounts, names, links, etc.)
            $table->json('properties')->nullable();

            // Which workspace this belongs to
            $table->string('workspace')->nullable()->index();
            // e.g. erp, marketplace, freelance, booking, system

            // Immutable — no updated_at
            $table->timestamp('created_at')->useCurrent();

            // Composite index for efficient user-specific feeds
            $table->index(['user_id', 'workspace', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_events');
    }
};
