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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['email', 'whatsapp', 'both'])->default('both');
            $table->enum('status', ['draft', 'scheduled', 'sending', 'completed', 'paused'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('target_audience', ['all_users', 'specific_users', 'filtered'])->default('all_users');
            $table->json('filter_criteria')->nullable();
            $table->foreignId('whatsapp_channel_id')->nullable()->constrained('whatsapp_channels')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('total_recipients')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
