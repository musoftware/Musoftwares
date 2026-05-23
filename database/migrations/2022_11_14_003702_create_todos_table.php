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
        Schema::create('todos', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->bigInteger('project_id')->unsigned()->index()->nullable();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            $table->bigInteger('request_id')->unsigned()->index()->nullable();
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');

            $table->bigInteger('task_id')->unsigned()->index()->nullable();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');

            $table->text('title');
            $table->text('description')->nullable();

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
        Schema::dropIfExists('todos');
    }
};
