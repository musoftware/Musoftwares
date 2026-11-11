<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ready programs learned from paid deliveries, for internal use only: the chat prices a matching idea at the price the
 * program was sold for and the build starts from its code, but the customer is never shown or told about it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_starters', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->foreignId('source_project_id')->unique()->constrained('builder_projects')->cascadeOnDelete();
            // the delivered version the card and the code come from
            $table->unsignedInteger('version');
            $table->string('type', 30);
            $table->string('title');
            $table->text('summary');
            $table->json('features');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_starters');
    }
};
