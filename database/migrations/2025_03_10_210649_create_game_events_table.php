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
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id'); // معرف الجلسة
            $table->string('event'); // نوع الحدث (game_start, score_update, game_over)
            $table->json('data')->nullable(); // بيانات الحدث (مثلاً: النقاط المكتسبة)
            $table->bigInteger('timestamp'); // توقيت الحدث
            $table->string('previous_hash'); // التجزئة السابقة (لضمان سلسلة الأحداث)
            $table->string('current_hash'); // التجزئة الحالية
            $table->boolean('burned')->default(false); // التجزئة الحالية
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};
