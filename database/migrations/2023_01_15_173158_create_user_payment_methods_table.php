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
        Schema::create('user_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('type', 255);
            $table->string('name', 255)->nullable();
            $table->string('id_number', 255)->nullable();
            $table->string('mobile', 255)->nullable();
            $table->string('ewallet_provider', 255)->nullable();
            $table->string('bank_number', 255)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank', 255)->nullable();
            $table->string('payee_email', 255)->nullable();
            $table->string('bank_branch', 255)->nullable();

            $table->enum('status', ['pending', 'active', 'declined'])->default('pending');

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
        Schema::dropIfExists('user_payment_methods');
    }
};
