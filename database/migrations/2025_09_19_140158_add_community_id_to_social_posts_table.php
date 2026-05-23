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
        Schema::table('social_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('community_id')->nullable()->after('user_id');
            $table->foreign('community_id')->references('id')->on('communities')->onDelete('cascade');
            $table->index('community_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropForeign(['community_id']);
            $table->dropIndex(['community_id']);
            $table->dropColumn('community_id');
        });
    }
};
