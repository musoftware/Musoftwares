<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_pos_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('bank'); // bank, mobile
            $table->string('bank')->nullable();
            $table->string('bank_number')->nullable();
            $table->string('mobile')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('affiliate_pos_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('affiliate_pos_payment_methods')->cascadeOnDelete();
            $table->double('amount', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, approved, declined
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_pos_payment_requests');
        Schema::dropIfExists('affiliate_pos_payment_methods');
    }
};
