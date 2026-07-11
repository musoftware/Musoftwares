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
        Schema::create('recurring_notices', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('message')->nullable();

            $table->enum('type', ['info', 'success', 'warning', 'danger'])->default('info');

            $table->enum('recurring', ['day', 'week', 'month', 'year']);
            $table->integer('recurring_times')->default(1);
            $table->text('recurring_times_week')->nullable();
            $table->text('recurring_times_month')->nullable();
            $table->text('recurring_times_year')->nullable();

            $table->date('start_date')->nullable();
            $table->date('current_date')->nullable();

            $table->boolean('is_active')->default(true);

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
        Schema::dropIfExists('recurring_notices');
    }
};
