<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What the chat did not know and asked the programmer (the worker), with the answer it brought back. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('builder_engineer_questions')) {
            return;
        }

        Schema::create('builder_engineer_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_engineer_questions');
    }
};
