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
        Schema::create('gold_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index(); // Nullable for B2C directly
            $table->unsignedBigInteger('user_id')->index();
            
            $table->string('name'); // e.g. Wedding, Kids, Investment
            $table->string('goal_type')->nullable(); // Dropdown for goal classification
            
            $table->decimal('target_grams', 10, 2)->default(0);
            $table->decimal('target_amount', 12, 2)->default(0);
            
            $table->decimal('balance_grams', 10, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0); // Value in local currency
            
            $table->foreignId('currency_id')->default(1)->constrained('currencies')->onDelete('restrict');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
            
            // If users table is in the same DB:
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gold_wallets');
    }
};
