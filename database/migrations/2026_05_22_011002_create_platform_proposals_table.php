<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('project_name')->nullable();
            $table->text('project_details')->nullable();
            
            $table->decimal('total_cost_egp', 12, 2)->default(0);
            $table->decimal('total_duration_days', 8, 2)->default(0);
            
            $table->json('cost_breakdown')->nullable();
            $table->json('proposal_data')->nullable(); // summary, value_proposition, etc.
            
            $table->text('ascii_table')->nullable();
            
            $table->string('adjustment_type')->default('percentage');
            $table->decimal('adjustment_value', 12, 2)->default(0);
            
            $table->string('status')->default('draft'); // draft, converted_to_contract, discarded
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_proposals');
    }
};
