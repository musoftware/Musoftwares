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
        Schema::create('store_tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->nullable();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('version')->nullable();
            $table->string('download_url')->nullable();
            $table->string('category')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->boolean('requires_payment')->default(false);
            $table->string('whatsapp_number')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->json('features')->nullable();
            $table->foreignId('serial_software_id')
                ->nullable()
                ->constrained('serial_softwares')
                ->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'sort_order']);
            $table->index('serial_software_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_tools');
    }
};
