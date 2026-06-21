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
        if (!Schema::hasTable('freelance_profiles')) {
            Schema::create('freelance_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title')->nullable();
                $table->text('bio')->nullable();
                $table->decimal('hourly_rate', 20, 8)->default(0);
                $table->boolean('receive_job_notifications')->default(true);
                $table->timestamp('notifications_muted_until')->nullable();
                $table->timestamps();
            $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_profiles');
    }
};
