<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_projects', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 20)->default('chatting')->index();
            $table->string('locale', 5)->default('ar');
            $table->string('ip', 45)->nullable()->index();
            $table->string('title')->nullable();
            $table->longText('spec')->nullable();
            $table->unsignedInteger('estimated_tokens')->default(0);
            $table->unsignedInteger('quote_version')->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('budget_usd', 10, 4)->default(0);
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_whatsapp', 50)->nullable();
            $table->decimal('paid_total', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('progress_step', 30)->nullable();
            $table->string('progress_file')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('lease_expires_at')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->timestamp('built_at')->nullable();
            $table->unsignedBigInteger('chat_input_tokens')->default(0);
            $table->unsignedBigInteger('chat_output_tokens')->default(0);
            $table->decimal('build_cost_usd', 10, 4)->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('builder_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('builder_projects')->cascadeOnDelete();
            $table->string('role', 10);
            $table->text('content');
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_messages');
        Schema::dropIfExists('builder_projects');
    }
};
