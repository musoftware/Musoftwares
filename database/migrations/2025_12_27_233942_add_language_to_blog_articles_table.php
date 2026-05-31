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
        Schema::table('blog_articles', function (Blueprint $table) {
            if (!Schema::hasColumn('blog_articles', 'language')) {
                $table->string('language', 5)->default('en');
            }
            if (!Schema::hasColumn('blog_articles', 'group_id')) {
                $table->uuid('group_id')->nullable()->after('language'); // To group translations together
                $table->index(['language', 'group_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_articles', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('blog_articles', 'language')) {
                $columns[] = 'language';
            }
            if (Schema::hasColumn('blog_articles', 'group_id')) {
                $columns[] = 'group_id';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
