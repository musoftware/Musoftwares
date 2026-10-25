<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menus made with the free QR menu maker. No account: whoever holds the edit token can change it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('free_qr_menus', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('edit_token_hash', 64);
            $table->json('data');
            $table->unsignedInteger('views')->default(0);
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_qr_menus');
    }
};
