<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('noteable');
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->string('type')->default('general');
            $table->string('visibility')->default('staff_only');
            $table->string('risk_level')->default('none');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notes');
    }
};
