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
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedBigInteger('creator_id');
            $table->enum('type', ['public', 'private'])->default('public');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->integer('member_count')->default(0);
            $table->integer('post_count')->default(0);
            $table->timestamps();
            
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
