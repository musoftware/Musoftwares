<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // the live database got this table before the migration was found on Linux; then there is nothing to do
        if (Schema::hasTable('gateway_clients')) {
            return;
        }

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
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_clients');
    }
};
