<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A site the customer wants copied, walked through by the worker (with their test login), and what it found. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('builder_site_tours')) {
            return;
        }

        Schema::create('builder_site_tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
            $table->string('url', 2000);
            $table->string('status', 20)->default('pending');
            $table->boolean('logged_in')->default(false);
            $table->unsignedSmallInteger('pages_seen')->default(0);
            $table->string('reason', 300)->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_site_tours');
    }
};
