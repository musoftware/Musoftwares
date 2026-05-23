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
        Schema::create('project_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('project_name');
            $table->text('project_details');
            $table->decimal('total_cost_egp', 15, 2);
            $table->decimal('total_duration_days', 10, 2);
            $table->json('cost_breakdown')->nullable();
            $table->json('proposal_data')->nullable();
            $table->text('ascii_table')->nullable();
            $table->string('adjustment_type')->default('percentage');
            $table->decimal('adjustment_value', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_proposals');
    }
};
