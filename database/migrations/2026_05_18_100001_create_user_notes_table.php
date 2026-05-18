<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create user_notes table — admin notes on specific users.
 * Recovered from old project: UserCredential model (user_credentials table).
 * Renamed to user_notes for clarity. Categories: password, anydesk, notes, archived.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();

            // Category system — recovered from old project
            // Values: password | anydesk | notes | archived
            $table->string('category')->default('notes');

            // Stores the original category before archiving, for restoration
            // Recovered from old project: original_category column
            $table->string('original_category')->nullable();

            $table->string('title');
            $table->text('content'); // renamed from 'note' for clarity

            $table->timestamps();

            $table->index(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notes');
    }
};
