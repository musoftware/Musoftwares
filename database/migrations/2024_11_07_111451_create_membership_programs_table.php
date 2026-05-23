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
        Schema::create('membership_programs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('membership_id')->nullable();
            $table->foreign('membership_id')->references('id')->on('memberships')->nullOnDelete();

            $table->unsignedBigInteger('software_program_id')->nullable();
            $table->foreign('software_program_id')->references('id')->on('software_programs')->nullOnDelete();

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
        Schema::dropIfExists('membership_programs');
    }
};
