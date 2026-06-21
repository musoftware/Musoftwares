<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('booking_daily_metrics')) {
            Schema::create('booking_daily_metrics', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->date('date')->index();
                $table->integer('total_bookings')->default(0);
                $table->integer('completed_bookings')->default(0);
                $table->integer('cancelled_bookings')->default(0);
                $table->integer('no_show_bookings')->default(0);
                $table->decimal('total_revenue', 10, 2)->default(0);
                $table->string('currency_id', 3)->default('USD');
                
                $table->unique(['tenant_id', 'date', 'currency_id']);
                $table->timestamps();
            $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_daily_metrics');
    }
};
