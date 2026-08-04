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
        Schema::create('service_playbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained('marketplace_services')->nullOnDelete();
            $table->string('title');
            $table->longText('marketing_message')->nullable();
            $table->longText('pricing_info')->nullable();
            $table->longText('client_requirements')->nullable();
            $table->longText('execution_workflow')->nullable();
            $table->longText('thank_you_message')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_playbooks');
    }
};
