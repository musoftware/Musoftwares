<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->integer('points');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->timestamps(); // immutable
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('points_balance')->default(0)->after('phone');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('points_balance');
        });
        Schema::dropIfExists('point_transactions');
    }
};
