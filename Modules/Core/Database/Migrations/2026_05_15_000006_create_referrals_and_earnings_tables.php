<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add referrer_id to users to establish the hierarchy
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('referrer_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reference_type')->nullable(); // e.g., Invoice
            $table->string('reference_id')->nullable();
            $table->integer('level'); // 1 or 2
            $table->decimal('amount', 20, 8);
            $table->string('currency', 3);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('referral_earnings');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropColumn('referrer_id');
        });
    }
};
