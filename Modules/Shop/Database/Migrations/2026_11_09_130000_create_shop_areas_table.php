<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_areas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('angazny_id')->unique();
            $table->unsignedBigInteger('city_id')->index();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('angazny_area_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->dropColumn('angazny_area_id');
        });

        Schema::dropIfExists('shop_areas');
    }
};
