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
        if (Schema::hasTable('gold_prices')) {
            Schema::table('gold_prices', function (Blueprint $table) {
                if (!Schema::hasColumn('gold_prices', 'date')) {
                    $table->date('date')->nullable()->unique();
                }
                if (!Schema::hasColumn('gold_prices', 'karat_24')) {
                    $table->decimal('karat_24', 10, 2)->nullable();
                }
                if (!Schema::hasColumn('gold_prices', 'karat_21')) {
                    $table->decimal('karat_21', 10, 2)->nullable();
                }
                if (!Schema::hasColumn('gold_prices', 'karat_18')) {
                    $table->decimal('karat_18', 10, 2)->nullable();
                }
                if (!Schema::hasColumn('gold_prices', 'currency')) {
                    $table->string('currency', 3)->default('EGP');
                }
                if (!Schema::hasColumn('gold_prices', 'global_price_usd')) {
                    $table->decimal('global_price_usd', 10, 2)->nullable();
                }
            });
        } else {
            Schema::create('gold_prices', function (Blueprint $table) {
                $table->id();
                $table->date('date')->unique();
                $table->decimal('karat_24', 10, 2)->nullable();
                $table->decimal('karat_21', 10, 2)->nullable();
                $table->decimal('karat_18', 10, 2)->nullable();
                $table->string('currency', 3)->default('EGP');
                $table->decimal('global_price_usd', 10, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gold_prices');
    }
};
