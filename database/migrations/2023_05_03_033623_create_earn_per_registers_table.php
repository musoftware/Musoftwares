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
        Schema::create('earn_per_registers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unsignedBigInteger('referred_user_id')->nullable();
            $table->foreign('referred_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->string('ip', 255);
            $table->string('iso_code', 5)->nullable();
            $table->integer('ip_block')->nullable();

            $table->double('amount', 23, 3);
            $table->bigInteger('currency')->unsigned()->index()->default(1);
            $table->foreign('currency')->references('id')->on('currencies');

            $table->enum('status', ['earn_blocked', 'reviewing', 'earned', 'ip_detected_as_proxy', 'repeat_ip']);

            $table->bigInteger('earning_id')->unsigned()->index()->nullable();
            $table->foreign('earning_id')->references('id')->on('earnings');

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
        Schema::dropIfExists('earn_per_registers');
    }
};
