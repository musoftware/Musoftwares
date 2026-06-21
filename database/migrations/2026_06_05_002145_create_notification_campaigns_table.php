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
        if (!Schema::hasTable('notification_campaigns')) {
            Schema::create('notification_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('body');
                $table->string('target_url')->nullable();
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('clicks_count')->default(0);
                $table->string('status')->default('sent');
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
        Schema::dropIfExists('notification_campaigns');
    }
};
