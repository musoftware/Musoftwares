<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('service_share_unlocks')) {
            Schema::create('service_share_unlocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('service_id')->constrained()->onDelete('cascade');
                $table->string('platform')->nullable(); // facebook, twitter, etc.
                $table->timestamp('shared_at')->useCurrent();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('service_share_unlocks');
    }
};
