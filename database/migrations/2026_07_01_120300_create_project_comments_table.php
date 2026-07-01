<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();

            // Polymorphic target: a comment may be attached to a Task, ProjectReport, ProjectBoardNote, etc.
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');

            $table->text('body');

            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_comments');
    }
};
