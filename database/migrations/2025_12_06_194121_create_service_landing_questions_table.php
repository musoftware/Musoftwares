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
        Schema::create('service_landing_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('landing_page_id');
            $table->foreign('landing_page_id')->references('id')->on('service_landing_pages')->onDelete('cascade');
            $table->string('question_text');
            $table->enum('field_type', ['text', 'textarea', 'email', 'phone', 'select', 'radio', 'checkbox', 'number', 'date'])->default('text');
            $table->json('field_options')->nullable(); // For select, radio, checkbox options
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_landing_questions');
    }
};
