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
        Schema::create('erp_branch_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->index();
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['branch_id', 'key']);
            $table->foreign('branch_id')->references('id')->on('erp_branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_branch_settings');
    }
};
