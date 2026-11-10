<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the site last heard from each worker. Kept in the database, not the cache: the live server's cache
 * forgets everything between requests, and the site never calls the worker PC, so it must remember here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('builder_worker_heartbeats')) {
            return;
        }

        Schema::create('builder_worker_heartbeats', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 40)->unique();
            $table->timestamp('last_seen_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_worker_heartbeats');
    }
};
