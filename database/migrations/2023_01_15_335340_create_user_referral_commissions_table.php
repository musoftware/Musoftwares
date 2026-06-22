<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserReferralCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('user_referral_commissions')) {
            Schema::create('user_referral_commissions', function (Blueprint $table) {
                $table->id();
    
                $table->bigInteger('user_id')->unsigned()->nullable();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    
                $table->bigInteger('referred_user_id')->unsigned()->nullable();
                $table->foreign('referred_user_id')->references('id')->on('users')->onDelete('cascade');
    
                $table->bigInteger('referred_invoice_id')->unsigned()->nullable();
                $table->foreign('referred_invoice_id')->references('id')->on('invoices')->onDelete('cascade');
    
                $table->bigInteger('currency')->unsigned()->index();
                $table->foreign('currency')->references('id')->on('currencies')->onDelete('cascade');
    
                $table->double('amount', 23, 3)->default('0');
    
                $table->date('convert_to_balance_on')->nullable();
    
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_referral_withdraws');
    }
}
