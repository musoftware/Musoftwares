<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Workspace Notes — tenant-scoped scratchpad notes.
 * Used by the ERP dashboard Notes section.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('title')->default('Untitled Note');
            $table->text('content')->nullable();
            $table->string('category', 50)->default('Internal'); // Internal | Client | Project
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_notes');
    }
};
