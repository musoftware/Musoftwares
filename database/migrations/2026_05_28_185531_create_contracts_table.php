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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('project_proposal_id')->nullable()->constrained()->onDelete('set null');
            $table->string('project_name');
            $table->longText('description')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->integer('currency_id')->default(1);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'signed', 'active', 'completed'])->default('draft');
            $table->json('content')->nullable(); // For detailed terms
            $table->text('payment_terms')->nullable();
            $table->boolean('deposit_paid')->default(false);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->longText('client_signature')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
