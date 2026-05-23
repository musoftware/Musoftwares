<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_histories', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('service_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('user_id')->constrained();
            $blueprint->string('title');
            $blueprint->decimal('price', 15, 2);
            $blueprint->string('change_reason')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_histories');
    }
};
