<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'default_ai_model')) {
                $table->string('default_ai_model')->nullable()->after('openai_api_key');
            }
            if (!Schema::hasColumn('users', 'openai_model')) {
                $table->string('openai_model')->nullable()->after('default_ai_model');
            }
            if (!Schema::hasColumn('users', 'gemini_api')) {
                $table->string('gemini_api')->nullable()->after('openai_model');
            }
            if (!Schema::hasColumn('users', 'gemini_model')) {
                $table->string('gemini_model')->nullable()->after('gemini_api');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('users', 'default_ai_model')) $cols[] = 'default_ai_model';
            if (Schema::hasColumn('users', 'openai_model')) $cols[] = 'openai_model';
            if (Schema::hasColumn('users', 'gemini_api')) $cols[] = 'gemini_api';
            if (Schema::hasColumn('users', 'gemini_model')) $cols[] = 'gemini_model';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
