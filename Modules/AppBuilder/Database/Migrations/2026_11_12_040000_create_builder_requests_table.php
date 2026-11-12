<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * After a project started and was paid, every change the customer asks for is one request in a list beside the chat:
 * free ones go to the engineer by themselves, priced ones wait until the customer pays for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('builder_requests')) {
            return;
        }

        Schema::create('builder_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('builder_messages')->nullOnDelete();
            $table->string('title');
            $table->text('spec');
            $table->text('customer_words')->nullable();
            // fix (to what the program has or promises) or build (something new)
            $table->string('kind', 10)->default('build');
            $table->boolean('free')->default(false);
            $table->boolean('complex')->default(false);
            $table->unsignedInteger('estimated_tokens')->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('referral_commission', 10, 2)->default(0);
            $table->decimal('budget_usd', 10, 4)->default(0);
            $table->string('model', 80)->nullable();
            $table->string('status', 20)->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->unsignedInteger('built_in_version')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_requests');
    }
};
