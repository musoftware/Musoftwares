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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->string('embed_token')->nullable()->after('user_id');
            $table->string('form_title')->nullable()->after('embed_token');
            $table->text('form_description')->nullable()->after('form_title');
            $table->string('button_text')->nullable()->after('form_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'embed_token', 'form_title', 'form_description', 'button_text']);
        });
    }
};
