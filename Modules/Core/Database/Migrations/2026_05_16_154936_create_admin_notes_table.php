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
        Schema::create('admin_notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('noteable');
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();

            $table->enum('visibility', [
                'private',
                'staff_only',
                'admins_only'
            ])->default('staff_only');

            $table->enum('type', [
                'general',
                'warning',
                'fraud_risk',
                'accounting',
                'moderation',
                'legal',
                'support'
            ])->default('general');

            $table->longText('content');
            $table->boolean('is_pinned')->default(false);

            $table->enum('risk_level', [
                'none',
                'low',
                'medium',
                'high',
                'critical'
            ])->default('none');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notes');
    }
};
