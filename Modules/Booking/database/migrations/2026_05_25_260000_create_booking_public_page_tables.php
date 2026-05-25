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
        Schema::create('booking_public_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('booking_public_page_themes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('page_id')->index();
            $table->string('primary_color')->default('#000000');
            $table->string('logo_url')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('font_family')->default('Inter');
            $table->timestamps();
            
            $table->foreign('page_id')->references('id')->on('booking_public_pages')->onDelete('cascade');
        });

        Schema::create('booking_public_page_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('page_id')->index();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('viewed_at')->useCurrent();
        });

        Schema::create('booking_public_booking_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('booking_id')->index();
            $table->string('source_ip')->nullable();
            $table->string('status')->default('initiated'); // initiated, completed, failed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_public_booking_logs');
        Schema::dropIfExists('booking_public_page_views');
        Schema::dropIfExists('booking_public_page_themes');
        Schema::dropIfExists('booking_public_pages');
    }
};
