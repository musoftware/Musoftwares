<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_watchlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->json('market_keys')->nullable();     // ["egypt_local", "global_xau"]
            $table->json('tracked_karats')->nullable();  // [24, 21, 18, 14]
            $table->json('tracked_currencies')->nullable(); // ["EGP", "USD", "SAR"]
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_watchlists');
    }
};
