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
        Schema::create('recurring_busy_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->boolean('is_recurring')->default(false);
            $table->string('day_of_week')->nullable(); // e.g. "Saturday", "Sunday"
            $table->date('specific_date')->nullable(); // For non-recurring, special dates
            
            $table->boolean('is_full_day')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            $table->text('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_busy_times');
    }
};
