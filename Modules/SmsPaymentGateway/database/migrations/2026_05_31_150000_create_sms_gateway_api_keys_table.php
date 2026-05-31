<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gateway_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name'); // "My E-Commerce Store"
            $table->string('publishable_key', 64)->unique(); // pk_live_xxxx / pk_test_xxxx
            $table->string('secret_key_hash', 128); // SHA-256 hash of sk_live_xxxx
            $table->string('secret_key_last_four', 4); // last 4 chars for display
            $table->boolean('is_test')->default(false); // false=live, true=test
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->json('permissions')->nullable(); // future: granular permissions
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index(['publishable_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateway_api_keys');
    }
};
