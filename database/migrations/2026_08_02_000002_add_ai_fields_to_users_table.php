<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_ai_model')->nullable()->after('openai_api_key');
            $table->string('openai_model')->nullable()->after('default_ai_model');
            $table->string('gemini_api')->nullable()->after('openai_model');
            $table->string('gemini_model')->nullable()->after('gemini_api');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['default_ai_model', 'openai_model', 'gemini_api', 'gemini_model']);
        });
    }
};
