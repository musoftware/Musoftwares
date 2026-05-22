<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('type')->nullable(); // received, used, earned, sent, refunded
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency')->nullable();
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('withdraw_id')->nullable();
            $table->unsignedBigInteger('user_referral_commission_id')->nullable();
            $table->unsignedBigInteger('reverse_transaction_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cost_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency')->nullable();
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cost_transactions');
        Schema::dropIfExists('transactions');
    }
};
