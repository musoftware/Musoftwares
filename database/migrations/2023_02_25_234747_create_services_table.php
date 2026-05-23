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
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->text('title');
            $table->text('description');
            $table->text('image');
            $table->double('price', 33, 3)->default('0');
            $table->enum('status', ['pending', 'reviewing', 'approved', 'declined'])->default('pending');

            $table->timestamps();
        });

        if (config('database.default') !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `services` ADD FULLTEXT INDEX title_desc_index (title, description)');
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `services` ADD FULLTEXT INDEX title_index (title)');
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `services` ADD FULLTEXT INDEX desc_index (description)');
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('services');
    }
};
