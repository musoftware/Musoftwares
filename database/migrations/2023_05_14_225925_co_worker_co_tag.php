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
        Schema::create('co_tech_tags_workers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('co_tech_tag_id')->nullable();
            $table->foreign('co_tech_tag_id')->references('id')->on('co_tech_tags')->nullOnDelete();

            $table->unsignedBigInteger('co_worker_id')->nullable();
            $table->foreign('co_worker_id')->references('id')->on('co_workers')->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
