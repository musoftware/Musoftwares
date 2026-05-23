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
        Schema::table('users', function (Blueprint $table) {

            $table->string('facebook')->nullable();
            $table->string('skype')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('phone_number2')->nullable();
            $table->string('job')->nullable();
            $table->string('address')->nullable();

            $table->dateTime('date_start')->nullable();
            $table->dateTime('date_end')->nullable();

            $table->integer('archived')->default('0');

            $table->bigInteger('currency')->unsigned()->index()->default(1);
            $table->foreign('currency')->references('id')->on('currencies');

            $table->boolean('client_taxable')->default(1);
            $table->boolean('invoice_taxable')->default(0);
            $table->boolean('timer_taxable')->default(1);

            $table->string('telegram_user_id')->nullable();

            $table->decimal('hour_rate', 9, 3)->default(12);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
