<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assignment_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_session_id')->constrained()->onDelete('cascade');
            $table->string('path'); // local path or URL

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_screenshots');
    }
};
