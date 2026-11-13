<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What the worker did on each project, call by call, so the owner can watch a build from the admin page. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('builder_worker_events')) {
            return;
        }

        Schema::create('builder_worker_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
            $table->string('worker', 60)->nullable();
            $table->string('kind', 30);
            $table->string('detail', 300)->nullable();
            // the same call again (polling for an answer, the same step) only counts up
            $table->unsignedInteger('repeats')->default(1);
            $table->timestamps();
            $table->index(['project_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_worker_events');
    }
};
