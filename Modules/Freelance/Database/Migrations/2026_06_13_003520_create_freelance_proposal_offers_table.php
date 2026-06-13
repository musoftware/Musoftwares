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
        Schema::create('freelance_proposal_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('freelance_proposals')->cascadeOnDelete();
            $table->foreignId('offered_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 20, 8)->default(0);
            $table->foreignId('currency_id')->default(2)->constrained('currencies')->onDelete('restrict');
            $table->string('status')->default('pending'); // pending, accepted, rejected, superseded
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_proposal_offers');
    }
};
