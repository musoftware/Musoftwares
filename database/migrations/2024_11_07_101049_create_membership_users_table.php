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
        Schema::create('membership_users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('membership_id')->nullable();
            $table->foreign('membership_id')->references('id')->on('memberships')->nullOnDelete();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->bigInteger('currency')->unsigned()->index();
            $table->foreign('currency')->references('id')->on('currencies')->onDelete('cascade');

            $table->double('amount', 23, 3)->default('0');

            $table->string('serial');

            $table->date('expires_at');

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
        Schema::dropIfExists('membership_users');
    }
};
