<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_lead', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, sent, failed, skipped
            $table->timestamp('sent_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_lead');
    }
};
