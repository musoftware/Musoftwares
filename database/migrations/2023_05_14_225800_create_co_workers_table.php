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
        Schema::create('co_workers', function (Blueprint $table) {
            $table->id();

            $table->string('person_name');
            $table->string('email')->nullable();
            $table->string('mobile', 14)->nullable();
            $table->string('facebook')->nullable();
            $table->string('linked_in')->nullable();
            $table->string('whatsapp')->nullable();
            $table->integer('time_from')->nullable();
            $table->integer('time_to')->nullable();


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
        Schema::dropIfExists('co_workers');
    }
};
