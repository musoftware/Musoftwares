<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Allow linking to ERP projects if they exist
            $table->unsignedBigInteger('project_id')->nullable();
            
            $table->string('project_name')->nullable();
            $table->text('project_description')->nullable();
            $table->string('reference')->unique()->nullable();
            $table->string('prepared_by')->nullable();
            
            $table->date('valid_until')->nullable();
            $table->string('duration')->nullable();
            
            $table->boolean('includes_hosting')->default(false);
            $table->string('hosting_duration')->nullable();
            $table->boolean('includes_support')->default(false);
            $table->string('support_duration')->nullable();
            
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            
            $table->json('features')->nullable();
            $table->json('items')->nullable();
            $table->json('content')->nullable(); // Lang, custom blocks
            $table->text('description')->nullable();
            
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency')->default('USD');
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            $table->string('status')->default('draft'); // draft, sent, signed, cancelled
            
            $table->text('payment_terms')->nullable();
            $table->boolean('deposit_paid')->default(false);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            
            $table->text('client_signature')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('client_name')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_contracts');
    }
};
