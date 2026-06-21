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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->text('ticket_subject');
            $table->text('ticket_message');

            $table->enum('ticket_status', ['open', 'closed', 'agent_replied', 'user_replied']);

            $table->integer('rate')->nullable();

            $table->bigInteger('assigned_employee_id')->nullable()->unsigned();
            $table->foreign('assigned_employee_id')->references('id')->on('users')->onDelete('set null');

            $table->enum('priority', ['low', 'medium', 'high'])->default('low');
            $table->timestamps();
            $table->softDeletes();

            $table->dateTime('closed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};
