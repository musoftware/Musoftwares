<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Images and PDFs the customer sends in the chat (a logo, a design, a document about their business). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('builder_attachments')) {
            return;
        }

        Schema::create('builder_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained('builder_messages')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->string('path');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_attachments');
    }
};
