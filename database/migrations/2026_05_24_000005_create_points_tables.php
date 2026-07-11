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
        if (! Schema::hasColumn('users', 'points_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('points_balance')->default(0)->after('user_balance');
            });
        }

        if (! Schema::hasTable('point_packages')) {
            Schema::create('point_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('points');
                $table->decimal('price', 10, 2);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('point_transactions')) {
            Schema::create('point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type'); // purchased, earned, used, refunded
                $table->integer('points');
                $table->string('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('point_packages');

        if (Schema::hasColumn('users', 'points_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('points_balance');
            });
        }
    }
};
