<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_product_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_product_id')->constrained('digital_products')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email');
            $table->string('download_token', 80)->unique();
            $table->dateTime('token_expires_at');
            $table->unsignedInteger('download_count')->default(0);
            $table->dateTime('last_downloaded_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['email', 'digital_product_id']);
            $table->index('download_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_product_downloads');
    }
};
