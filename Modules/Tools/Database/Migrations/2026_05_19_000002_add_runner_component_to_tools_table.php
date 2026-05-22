<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tools')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->string('runner_component')->nullable()->after('requirements')
                    ->comment('React runner component name: tiktok-scraper, viral-autopsy, hook-analyzer, format-extractor');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tools')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->dropColumn('runner_component');
            });
        }
    }
};
