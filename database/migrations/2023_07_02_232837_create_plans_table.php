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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->string('plan_name');
            $table->double('plan_price', 8, 2);
            $table->integer('plan_duration')->default(30);

            $table->bigInteger('plan_currency')->unsigned()->index()->default(1);
            $table->foreign('plan_currency')->references('id')->on('currencies');

            $table->double('hour_rate_price', 8, 2)->nullable();

            $table->boolean('new_feature_status')->default(0);
            $table->double('discount_new_feature_percentage', 8, 2)->nullable();

            $table->boolean('support_status')->default(0);
            $table->boolean('discount_support_percentage')->default(0);

            $table->boolean('plan_status')->default(1);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plans');
    }
};
