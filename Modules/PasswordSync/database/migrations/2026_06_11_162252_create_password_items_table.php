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
        Schema::table('password_vaults', function (Blueprint $table) {
            $table->string('salt')->nullable()->after('user_id');
        });

        Schema::create('password_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('password_vault_id')->constrained()->onDelete('cascade');
            $table->string('remote_id')->index(); // ID from extension
            $table->longText('encrypted_data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_items');
        
        Schema::table('password_vaults', function (Blueprint $table) {
            $table->dropColumn('salt');
        });
    }
};
