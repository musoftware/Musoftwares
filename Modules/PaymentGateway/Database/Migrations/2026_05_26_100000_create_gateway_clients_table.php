<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_id')->unique();          // pgw_xxxxx
            $table->string('client_secret');                // sk_xxxxx
            $table->string('webhook_secret')->nullable();   // whsec_xxxxx
            $table->string('website')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('allowed_ips')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(40.00); // 40%
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_clients');
    }
};
