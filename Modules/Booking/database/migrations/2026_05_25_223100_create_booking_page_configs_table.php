<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('booking_page_configs')) {
            Schema::create('booking_page_configs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->string('slug')->unique(); // e.g., 'my-clinic'
                $table->string('page_title')->nullable();
                $table->text('welcome_message')->nullable();
                $table->string('primary_color')->default('#000000');
                $table->string('logo_path')->nullable();
                $table->string('banner_path')->nullable();
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_page_configs');
    }
};
