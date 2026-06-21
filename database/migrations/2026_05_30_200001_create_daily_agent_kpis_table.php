<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_agent_kpis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            
            $table->integer('calls_made')->default(0);
            $table->integer('leads_closed')->default(0);
            $table->integer('total_assigned')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            
            $table->timestamps();
            $table->softDeletes();

            // Upsert unique key
            $table->unique(['tenant_id', 'user_id', 'date']);
            
            // Fast analytics indexing
            $table->index(['branch_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_agent_kpis');
    }
};
