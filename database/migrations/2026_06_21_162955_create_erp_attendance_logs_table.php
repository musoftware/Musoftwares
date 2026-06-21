<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('erp_tenants')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('erp_team_members')->onDelete('cascade');
            $table->date('date');
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->integer('total_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_attendance_logs');
    }
};
