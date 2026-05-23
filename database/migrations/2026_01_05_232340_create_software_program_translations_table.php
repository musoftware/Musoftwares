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
        Schema::create('software_program_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_program_id')->constrained()->onDelete('cascade');
            $table->string('locale');
            $table->string('field');
            $table->text('value');
            $table->timestamps();
            
            $table->unique(['software_program_id', 'locale', 'field'], 'sp_trans_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_program_translations');
    }
};
