<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_widgets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Public reference
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->enum('type', ['inline', 'popup'])->default('inline');
            $table->string('primary_color')->default('#000000');
            $table->string('button_text')->default('Book Now');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('booking_widget_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('widget_id')->index();
            $table->string('domain'); // e.g., myclinic.com
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('widget_id')->references('id')->on('booking_widgets')->onDelete('cascade');
        });

        Schema::create('booking_widget_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('widget_id')->index();
            $table->string('action'); // view, start_booking, complete_booking
            $table->string('visitor_ip')->nullable();
            $table->string('origin_domain')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('widget_id')->references('id')->on('booking_widgets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_widget_logs');
        Schema::dropIfExists('booking_widget_domains');
        Schema::dropIfExists('booking_widgets');
    }
};
