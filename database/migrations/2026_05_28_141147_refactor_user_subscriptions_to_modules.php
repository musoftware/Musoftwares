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
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']); // if it has a foreign key
            $table->dropColumn('plan_id');
            $table->string('object')->after('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn('object');
            $table->unsignedBigInteger('plan_id')->nullable()->after('client_id');
            $table->foreign('plan_id')->references('id')->on('module_plans')->onDelete('cascade');
        });
    }
};
