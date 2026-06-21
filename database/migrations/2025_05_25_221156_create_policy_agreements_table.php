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
        Schema::create('policy_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->ipAddress('ip_address');
            $table->string('user_agent')->nullable();

            $table->longText('policy_snapshot')->nullable(); // نسخة HTML للسياسة
            $table->string('version')->default('v1.0');
            $table->timestamp('agreed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_agreements');
    }
};
