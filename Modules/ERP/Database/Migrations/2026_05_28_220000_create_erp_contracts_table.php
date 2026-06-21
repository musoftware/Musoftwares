<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->decimal('value', 15, 2)->default(0.00);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('status')->default('Draft');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('tenant_id')->references('id')->on('erp_tenants')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('erp_tenant_clients')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_contracts');
    }
};
