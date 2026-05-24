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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->bigInteger('project_id')->unsigned()->index()->nullable();
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            $table->bigInteger('request_id')->unsigned()->index()->nullable();
            $table->foreign('request_id')->references('id')->on('requests')->onDelete('cascade');

            $table->double('paid', 23, 3)->default(0);

            $table->double('unpaid', 23, 3)->default(0);

            $table->bigInteger('currency')->unsigned()->index()->default(1);
            $table->foreign('currency')->references('id')->on('currencies');

            $table->double('tax_value', 23, 3)->default(0);
            $table->double('discount', 23, 3)->default(0);

            $table->enum('status', ['unpaid', 'paid', 'partially_paid', 'cancelled'])->default('unpaid');

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
        Schema::dropIfExists('invoices');
    }
};
