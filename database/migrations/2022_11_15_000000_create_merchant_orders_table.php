<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchantOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('merchant_orders')) {
            Schema::create('merchant_orders', function (Blueprint $table) {
                $table->id();

                $table->bigInteger('user_id')->unsigned()->index();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                $table->bigInteger('currency')->unsigned()->index();
                $table->foreign('currency')->references('id')->on('currencies')->onDelete('cascade');

                $table->double('amount', 23, 3)->default('0');

                $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
                $table->longText('data')->nullable();
                $table->text('error')->nullable();

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
        Schema::dropIfExists('merchant_orders');
    }
}
