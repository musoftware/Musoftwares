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
        // 1. booking_white_label_settings
        Schema::create('booking_white_label_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('font_family')->nullable();
            $table->text('custom_css')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 2. booking_white_label_assets
        Schema::create('booking_white_label_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('type'); // logo, favicon, email_banner
            $table->string('path');
            $table->string('disk')->default('s3');
            $table->string('url')->nullable();
            $table->timestamps();
        });

        // 3. booking_white_label_domains
        Schema::create('booking_white_label_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('domain')->unique();
            $table->string('status')->default('pending'); // pending, active, failed
            $table->string('txt_record')->nullable();
            $table->string('ssl_status')->default('pending');
            $table->timestamps();
        });

        // 4. booking_white_label_themes
        Schema::create('booking_white_label_themes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->json('settings_json')->nullable();
            $table->timestamps();
        });

        // 5. booking_white_label_templates
        Schema::create('booking_white_label_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('type'); // email_confirmation, wa_reminder, sms_reminder
            $table->text('body');
            $table->string('subject')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_white_label_templates');
        Schema::dropIfExists('booking_white_label_themes');
        Schema::dropIfExists('booking_white_label_domains');
        Schema::dropIfExists('booking_white_label_assets');
        Schema::dropIfExists('booking_white_label_settings');
    }
};
