<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('written_course_search_index', function (Blueprint $table) {
            $table->id();
            $table->string('course_slug');
            $table->string('module_slug')->nullable();
            $table->string('lesson_slug')->nullable();
            $table->string('title');
            $table->longText('content_text');
            $table->timestamps();
            $table->softDeletes();
            
            // Full text index for simple searching if Meilisearch is not available
            // $table->fullText(['title', 'content_text']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('written_course_search_index');
    }
};
