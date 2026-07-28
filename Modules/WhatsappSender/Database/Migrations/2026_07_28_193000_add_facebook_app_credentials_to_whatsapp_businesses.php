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
        Schema::table('whatsapp_businesses', function (Blueprint $table) {
            $table->string('facebook_client_id')->nullable()->after('webhook_verify_token');
            $table->string('facebook_client_secret')->nullable()->after('facebook_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_businesses', function (Blueprint $table) {
            $table->dropColumn(['facebook_client_id', 'facebook_client_secret']);
        });
    }
};
