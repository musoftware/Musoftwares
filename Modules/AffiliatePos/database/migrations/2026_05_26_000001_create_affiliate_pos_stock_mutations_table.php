<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_pos_stock_mutations', function (Blueprint $table) {
            $table->id();
            
            $table->string("stockable_type", 140);
            $table->unsignedBigInteger("stockable_id");
            $table->index(["stockable_type", "stockable_id"]);

            $table->string('reference_type', 130)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->integer('amount');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_pos_stock_mutations');
    }
};
