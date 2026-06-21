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
        Schema::create('booking_wa_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('trigger_type')->index(); // e.g., 'before_1_hour', 'on_booking_confirmed'
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Use soft deletes if needed later, but standard architecture often skips it for config tables
            // $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_wa_templates');
    }
};
