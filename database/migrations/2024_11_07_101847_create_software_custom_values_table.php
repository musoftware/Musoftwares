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
        Schema::create('software_custom_values', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('software_program_id')->nullable();
            $table->foreign('software_program_id')->references('id')->on('software_programs')->nullOnDelete();

            $table->string('key');
            $table->text('value');

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
        Schema::dropIfExists('software_custom_values');
    }
};
